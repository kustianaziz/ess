<?php

namespace App\Http\Controllers;

use App\Actions\Shared\GenerateRequestNumberAction;
use App\Actions\Shared\RecordStatusHistoryAction;
use App\Enums\RequestStatus;
use App\Models\Attachment;
use App\Models\BusinessTripExpenseItem;
use App\Models\BusinessTripRequest;
use App\Models\BusinessTripSettlement;
use App\Models\User;
use App\Notifications\RequestSubmittedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class BusinessTripController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $request->user()->load('division');
        $subordinates = $user->subordinates()->select('id', 'name', 'position', 'nik')->get();

        return Inertia::render('Pengajuan/PerjalananDinas/Create', [
            'applicant' => [
                'name' => $user->name,
                'nik' => $user->nik,
                'division' => $user->division?->name ?? '-',
                'position' => $user->position ?? '-',
                'submission_date' => now()->translatedFormat('d F Y'),
            ],
            'subordinates' => $subordinates,
        ]);
    }

    public function store(
        Request $request,
        GenerateRequestNumberAction $generateRequestNumber,
        RecordStatusHistoryAction $recordStatusHistory
    ): RedirectResponse {
        $validated = $request->validate([
            'destination' => 'required|string|max:255',
            'target_institution' => 'nullable|string|max:255',
            'purpose' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'transportation_type' => 'nullable|string|max:255',
            'assignment_letter_number' => 'nullable|string|max:255',
            'estimated_budget' => 'required|numeric|min:0',
            'attachments' => 'nullable|array',
            'action' => 'required|in:draft,submit',
            'is_delegated' => 'boolean',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $user = $request->user();
        $isSubmit = $validated['action'] === 'submit';
        $isDelegated = $request->boolean('is_delegated');
        
        $status = $isSubmit ? ($isDelegated ? RequestStatus::APPROVED : RequestStatus::SUBMITTED) : RequestStatus::DRAFT;
        $currentLevel = $isSubmit ? ($isDelegated ? 2 : 1) : 0;

        $tripRequest = DB::transaction(function () use (
            $validated,
            $user,
            $status,
            $currentLevel,
            $isSubmit,
            $isDelegated,
            $generateRequestNumber,
            $recordStatusHistory
        ) {
            $requestNumber = $generateRequestNumber->execute('PD', 'business_trip_requests');

            $trip = BusinessTripRequest::create([
                'request_number' => $requestNumber,
                'user_id' => $user->id,
                'is_delegated' => $isDelegated,
                'assigned_to' => $isDelegated ? $validated['assigned_to'] : null,
                'assignment_letter_number' => $validated['assignment_letter_number'] ?? null,
                'destination' => $validated['destination'],
                'target_institution' => $validated['target_institution'] ?? null,
                'purpose' => $validated['purpose'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'transportation_type' => $validated['transportation_type'] ?? 'Pesawat / Kendaraan Umum',
                'estimated_budget' => $validated['estimated_budget'],
                'status' => $status,
                'current_approval_level' => $currentLevel,
                'submitted_at' => $isSubmit ? now() : null,
            ]);

            // Save attachments
            if (!empty($validated['attachments'])) {
                foreach ($validated['attachments'] as $file) {
                    $path = $file->store('attachments/perjalanan-dinas', 'public');
                    $trip->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => $user->id,
                    ]);
                }
            }

            // Record status history
            $historyNote = $isSubmit ? 'Pengajuan perjalanan dinas dikirim.' : 'Disimpan sebagai draf.';
            if ($isDelegated && $isSubmit) {
                $historyNote = 'Penugasan perjalanan dinas dari atasan (Otomatis disetujui Level 1 & 2).';
            }

            $recordStatusHistory->execute($trip, null, $status->value, $user->id, $historyNote);

            if ($isSubmit && !$isDelegated) {
                // Initialize approval chain
                app(\App\Actions\Shared\CreateInitialApprovalAction::class)->execute($trip, $user, 'perjalanan-dinas');
            } elseif ($isSubmit && $isDelegated && $trip->assigned_to) {
                $assignee = User::find($trip->assigned_to);
                if ($assignee) {
                    Notification::send($assignee, new \App\Notifications\TaskDelegatedNotification(
                        'perjalanan-dinas',
                        $trip->id,
                        $trip->request_number,
                        $user->name
                    ));
                }
            }

            return $trip;
        });

        $message = $isSubmit ? ($isDelegated ? 'Penugasan tim berhasil dibuat dan disetujui otomatis!' : 'Pengajuan berhasil dikirim!') : 'Draf berhasil disimpan!';
        return redirect()->route('riwayat-pengajuan.index')->with('success', $message);
    }

    public function settlementCreate(Request $request, int $id): Response
    {
        $user = $request->user();
        $tripRequest = BusinessTripRequest::with(['user.division', 'attachments'])
            ->where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('assigned_to', $user->id);
            })
            ->firstOrFail();

        return Inertia::render('Pengajuan/PerjalananDinas/Settlement', [
            'tripRequest' => [
                'id' => $tripRequest->id,
                'request_number' => $tripRequest->request_number,
                'destination' => $tripRequest->destination,
                'purpose' => $tripRequest->purpose,
                'start_date' => $tripRequest->start_date->format('Y-m-d'),
                'end_date' => $tripRequest->end_date->format('Y-m-d'),
                'estimated_budget' => $tripRequest->disbursed_budget ?? 0,
                'estimated_budget_formatted' => 'Rp ' . number_format($tripRequest->disbursed_budget ?? 0, 0, ',', '.'),
                'allowance_breakdown' => $tripRequest->allowance_breakdown ?? [],
            ],
            'applicant' => [
                'name' => $user->name,
                'nik' => $user->nik,
                'division' => $user->division?->name ?? '-',
                'position' => $user->position ?? '-',
            ],
        ]);
    }

    public function settlementStore(
        Request $request,
        int $id,
        GenerateRequestNumberAction $generateRequestNumber
    ): RedirectResponse {
        $user = $request->user();
        $tripRequest = BusinessTripRequest::where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('assigned_to', $user->id);
            })
            ->firstOrFail();

        $validated = $request->validate([
            'trip_report' => 'required|string',
            'expense_items' => 'required|array|min:1',
            'expense_items.*.category' => 'required|string',
            'expense_items.*.description' => 'required|string',
            'expense_items.*.amount' => 'required|numeric|min:0',
            'expense_items.*.expense_date' => 'required|date',
            'attachments' => 'nullable|array',
        ]);

        $totalActualCost = collect($validated['expense_items'])->sum('amount');
        $advanceAmount = $tripRequest->disbursed_budget ?? 0;
        $differenceAmount = $totalActualCost - $advanceAmount; // positive = less paid, negative = refund to company

        DB::transaction(function () use (
            $validated,
            $user,
            $tripRequest,
            $totalActualCost,
            $advanceAmount,
            $differenceAmount,
            $generateRequestNumber
        ) {
            $settlementNumber = $generateRequestNumber->execute('PD-SL', 'business_trip_settlements', 'settlement_number');

            $settlement = BusinessTripSettlement::create([
                'settlement_number' => $settlementNumber,
                'business_trip_request_id' => $tripRequest->id,
                'user_id' => $user->id,
                'total_actual_cost' => $totalActualCost,
                'advance_amount' => $advanceAmount,
                'difference_amount' => $differenceAmount,
                'trip_report' => $validated['trip_report'],
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            foreach ($validated['expense_items'] as $item) {
                $settlement->expenseItems()->create([
                    'category' => $item['category'],
                    'description' => $item['description'],
                    'amount' => $item['amount'],
                    'expense_date' => $item['expense_date'],
                ]);
            }

            if (!empty($validated['attachments'])) {
                foreach ($validated['attachments'] as $file) {
                    $path = $file->store('attachments/settlements', 'public');
                    $settlement->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => $user->id,
                    ]);
                }
            }

            $tripRequest->update(['status' => RequestStatus::COMPLETED]);
        });

        return redirect()->route('riwayat-pengajuan.index')->with(
            'success',
            "Penyelesaian Perjalanan Dinas {$tripRequest->request_number} berhasil disubmit untuk diverifikasi Finance!"
        );
    }
}

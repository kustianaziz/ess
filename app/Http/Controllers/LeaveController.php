<?php

namespace App\Http\Controllers;

use App\Actions\Leave\CalculateLeaveDaysAction;
use App\Actions\Shared\GenerateRequestNumberAction;
use App\Actions\Shared\RecordStatusHistoryAction;
use App\Enums\RequestStatus;
use App\Models\Approval;
use App\Models\Attachment;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $request->user()->load('division');
        $leaveTypes = LeaveType::where('is_active', true)->get();

        // Get leave balances for current user in current year
        $balances = LeaveBalance::where('user_id', $user->id)
            ->where('year', date('Y'))
            ->get()
            ->keyBy('leave_type_id');

        $leaveTypesFormatted = $leaveTypes->map(function ($type) use ($balances) {
            $bal = $balances->get($type->id);
            return [
                'id' => $type->id,
                'name' => $type->name,
                'requires_attachment' => $type->requires_attachment,
                'quota' => $bal?->quota ?? $type->default_quota ?? 12,
                'used' => $bal?->used ?? 0,
                'remaining' => $bal?->remaining ?? $type->default_quota ?? 12,
            ];
        });

        $colleagues = User::where('id', '!=', $user->id)
            ->where('status', 'active')
            ->select('id', 'name', 'position', 'nik')
            ->get();

        return Inertia::render('Pengajuan/Cuti/Create', [
            'applicant' => [
                'name' => $user->name,
                'nik' => $user->nik,
                'division' => $user->division?->name ?? '-',
                'position' => $user->position ?? '-',
                'submission_date' => now()->translatedFormat('d F Y'),
            ],
            'leaveTypes' => $leaveTypesFormatted,
            'colleagues' => $colleagues,
        ]);
    }

    public function store(
        Request $request,
        CalculateLeaveDaysAction $calculateDays,
        GenerateRequestNumberAction $generateNumber,
        RecordStatusHistoryAction $recordHistory
    ): RedirectResponse {
        $isDraft = $request->input('action') === 'draft';

        $validated = $request->validate([
            'leave_type_id' => $isDraft ? 'nullable|exists:leave_types,id' : 'required|exists:leave_types,id',
            'start_date' => $isDraft ? 'nullable|date' : 'required|date',
            'end_date' => $isDraft ? 'nullable|date|after_or_equal:start_date' : 'required|date|after_or_equal:start_date',
            'reason' => $isDraft ? 'nullable|string' : 'required|string',
            'handover_to_user_id' => 'nullable|exists:users,id',
            'handover_notes' => 'nullable|string',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user = $request->user();

        // Calculate total working days
        $totalDays = (!empty($validated['start_date']) && !empty($validated['end_date']))
            ? $calculateDays->execute($validated['start_date'], $validated['end_date'])
            : 0;

        // Quota validation if submitting
        if (!$isDraft && $validated['leave_type_id']) {
            $balance = LeaveBalance::where('user_id', $user->id)
                ->where('leave_type_id', $validated['leave_type_id'])
                ->where('year', date('Y'))
                ->first();

            if ($balance && $balance->remaining < $totalDays) {
                return back()->withErrors([
                    'start_date' => "Sisa kuota cuti Anda ({$balance->remaining} hari) tidak mencukupi untuk pengajuan {$totalDays} hari cuti.",
                ]);
            }
        }

        return DB::transaction(function () use ($request, $validated, $user, $totalDays, $isDraft, $generateNumber, $recordHistory) {
            $status = $isDraft ? RequestStatus::DRAFT->value : RequestStatus::SUBMITTED->value;
            $requestNumber = $generateNumber->execute('CT', 'leave_requests');

            $leaveRequest = LeaveRequest::create([
                'request_number' => $requestNumber,
                'user_id' => $user->id,
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_days' => $totalDays,
                'reason' => $validated['reason'] ?? '',
                'handover_to_user_id' => $validated['handover_to_user_id'] ?? null,
                'handover_notes' => $validated['handover_notes'] ?? null,
                'status' => $status,
                'current_approval_level' => $isDraft ? 0 : 1,
                'submitted_at' => $isDraft ? null : now(),
            ]);

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments/leave', 'public');
                    Attachment::create([
                        'attachable_type' => LeaveRequest::class,
                        'attachable_id' => $leaveRequest->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => $user->id,
                    ]);
                }
            }

            // Record status history
            $recordHistory->execute(
                $leaveRequest,
                null,
                $status,
                $user->id,
                $isDraft ? 'Pengajuan cuti disimpan sebagai draf.' : 'Pengajuan cuti dikirim untuk persetujuan.'
            );

            // Create initial approval records if submitted
            if (!$isDraft) {
                app(\App\Actions\Shared\CreateInitialApprovalAction::class)->execute($leaveRequest, $user, 'cuti');
            }

            $message = $isDraft ? 'Pengajuan cuti berhasil disimpan sebagai draft.' : 'Pengajuan cuti berhasil dikirim!';
            return redirect()->route('riwayat-pengajuan.index')->with('success', $message);
        });
    }

    public function quota(Request $request)
    {
        $user = $request->user();
        $balances = LeaveBalance::with('leaveType')
            ->where('user_id', $user->id)
            ->where('year', date('Y'))
            ->get();

        return response()->json($balances);
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Shared\GenerateRequestNumberAction;
use App\Actions\Shared\RecordStatusHistoryAction;
use App\Enums\RequestStatus;
use App\Models\Approval;
use App\Models\Attachment;
use App\Models\ExpenseType;
use App\Models\ReimbursementRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ReimbursementController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $request->user()->load('division');
        $expenseTypes = ExpenseType::where('is_active', true)->get();

        return Inertia::render('Pengajuan/Reimbursement/Create', [
            'applicant' => [
                'name' => $user->name,
                'nik' => $user->nik,
                'division' => $user->division?->name ?? '-',
                'position' => $user->position ?? '-',
                'submission_date' => now()->translatedFormat('d F Y'),
            ],
            'expenseTypes' => $expenseTypes,
        ]);
    }

    public function store(Request $request, GenerateRequestNumberAction $generateNumber, RecordStatusHistoryAction $recordHistory): RedirectResponse
    {
        $isDraft = $request->input('action') === 'draft';

        $validated = $request->validate([
            'expense_type_id' => $isDraft ? 'nullable|exists:expense_types,id' : 'required|exists:expense_types,id',
            'expense_date' => $isDraft ? 'nullable|date' : 'required|date',
            'amount' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:1000',
            'description' => $isDraft ? 'nullable|string' : 'required|string|max:1000',
            'attachments' => $isDraft ? 'nullable|array' : 'required|array|min:1',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($request, $validated, $user, $isDraft, $generateNumber, $recordHistory) {
            $status = $isDraft ? RequestStatus::DRAFT->value : RequestStatus::SUBMITTED->value;
            $requestNumber = $generateNumber->execute('RB', 'reimbursement_requests');

            $reimbursement = ReimbursementRequest::create([
                'request_number' => $requestNumber,
                'user_id' => $user->id,
                'expense_type_id' => $validated['expense_type_id'],
                'expense_date' => $validated['expense_date'],
                'amount' => $validated['amount'] ?? 0,
                'description' => $validated['description'] ?? '',
                'status' => $status,
                'current_approval_level' => $isDraft ? 0 : 1,
                'submitted_at' => $isDraft ? null : now(),
            ]);

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments/reimbursements', 'public');
                    Attachment::create([
                        'attachable_type' => ReimbursementRequest::class,
                        'attachable_id' => $reimbursement->id,
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
                $reimbursement,
                null,
                $status,
                $user->id,
                $isDraft ? 'Pengajuan disimpan sebagai draf.' : 'Pengajuan dikirim untuk persetujuan.'
            );

            // Create initial approval records if submitted
            if (!$isDraft) {
                app(\App\Actions\Shared\CreateInitialApprovalAction::class)->execute($reimbursement, $user, 'reimbursement');
            }

            $message = $isDraft ? 'Pengajuan reimbursement berhasil disimpan sebagai draft.' : 'Pengajuan reimbursement berhasil dikirim!';
            return redirect()->route('riwayat-pengajuan.index')->with('success', $message);
        });
    }
}

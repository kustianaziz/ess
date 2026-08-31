<?php

namespace App\Http\Controllers;

use App\Actions\Shared\GenerateRequestNumberAction;
use App\Actions\Shared\RecordStatusHistoryAction;
use App\Enums\RequestStatus;
use App\Models\Approval;
use App\Models\OvertimeClaim;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OvertimeController extends Controller
{
    public function create()
    {
        // Get all active users to be selected as Level 1 approver (Leader)
        $leaders = User::where('status', 'active')
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name', 'position']);

        return Inertia::render('Pengajuan/Lembur/Create', [
            'leaders' => $leaders,
        ]);
    }

    public function store(Request $request, GenerateRequestNumberAction $generateRequestNumber, RecordStatusHistoryAction $recordHistory)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'task_description' => 'required|string',
            'leader_id' => 'required|exists:users,id',
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($validated, $user, $generateRequestNumber, $recordHistory) {
            $requestNumber = $generateRequestNumber->execute('LMBR', 'overtime_requests', 'request_number');

            $overtime = OvertimeRequest::create([
                'request_number' => $requestNumber,
                'user_id' => $user->id,
                'date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'task_description' => $validated['task_description'],
                'status' => RequestStatus::SUBMITTED->value,
                'current_approval_level' => 1,
                'submitted_at' => now(),
            ]);

            Approval::create([
                'approvable_type' => OvertimeRequest::class,
                'approvable_id' => $overtime->id,
                'level' => 1,
                'approver_id' => $validated['leader_id'],
                'status' => 'pending',
            ]);

            $recordHistory->execute($overtime, RequestStatus::DRAFT->value, RequestStatus::SUBMITTED->value, $user->id, 'Pengajuan rencana lembur disubmit. Menunggu persetujuan Atasan.');

            // Send Notification to Leader
            $leader = \App\Models\User::find($validated['leader_id']);
            if ($leader) {
                $leader->notify(new \App\Notifications\RequestSubmittedNotification('lembur', $overtime->id, $requestNumber, $user->name));
            }
        });

        return redirect()->route('riwayat-pengajuan.index')->with('success', 'Rencana lembur berhasil diajukan!');
    }

    public function claimCreate(OvertimeRequest $overtimeRequest)
    {
        if ($overtimeRequest->status->value !== RequestStatus::APPROVED->value) {
            return redirect()->back()->withErrors(['error' => 'Rencana lembur ini belum disetujui, tidak bisa diklaim.']);
        }

        if ($overtimeRequest->claim) {
            return redirect()->back()->withErrors(['error' => 'Lembur ini sudah pernah diklaim.']);
        }

        // Get all managers or users to be selected as Level 2 approver
        // Assuming anyone with role 'manager' or maybe just fetch all active users
        $user = Auth::user();

        return Inertia::render('Pengajuan/Lembur/Claim', [
            'applicant' => $user,
            'overtimeRequest' => $overtimeRequest,
        ]);
    }

    public function claimStore(Request $request, OvertimeRequest $overtimeRequest, GenerateRequestNumberAction $generateRequestNumber, RecordStatusHistoryAction $recordHistory)
    {
        if ($overtimeRequest->status->value !== RequestStatus::APPROVED->value) {
            return redirect()->back()->withErrors(['error' => 'Rencana lembur ini belum disetujui.']);
        }
        if ($overtimeRequest->claim) {
            return redirect()->back()->withErrors(['error' => 'Lembur ini sudah pernah diklaim.']);
        }

        $validated = $request->validate([
            'actual_start_time' => 'required',
            'actual_end_time' => 'required',
            'amount' => 'required|numeric|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120',
            'proof_link' => 'nullable|string',
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($validated, $overtimeRequest, $user, $generateRequestNumber, $recordHistory, $request) {
            $claimNumber = $generateRequestNumber->execute('CLM-LMBR', 'overtime_claims', 'claim_number');

            $claim = OvertimeClaim::create([
                'claim_number' => $claimNumber,
                'overtime_request_id' => $overtimeRequest->id,
                'user_id' => $user->id,
                'actual_start_time' => $validated['actual_start_time'],
                'actual_end_time' => $validated['actual_end_time'],
                'amount' => $validated['amount'],
                'level2_approver_id' => $user->manager_id ?? $user->id,
                'status' => RequestStatus::SUBMITTED->value,
                'current_approval_level' => 1,
                'submitted_at' => now(),
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments/overtime', 'public');
                    $claim->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getMimeType(),
                    ]);
                }
            }

            // Ambil approver dari pengajuan rencana lembur sebelumnya
            $requestApproval = Approval::where('approvable_type', OvertimeRequest::class)
                ->where('approvable_id', $overtimeRequest->id)
                ->where('level', 1)
                ->first();
            
            $leaderId = $requestApproval ? $requestApproval->approver_id : ($user->manager_id ?? $user->id);

            // Create Level 1 Approval (Leader)
            Approval::create([
                'approvable_type' => OvertimeClaim::class,
                'approvable_id' => $claim->id,
                'level' => 1,
                'approver_id' => $leaderId,
                'status' => 'pending',
            ]);

            $recordHistory->execute($claim, RequestStatus::DRAFT->value, RequestStatus::SUBMITTED->value, $user->id, 'Klaim pencairan lembur disubmit. Menunggu persetujuan Level 1 (Atasan).');

            // Send Notification to Level 1 Approver
            $leader = \App\Models\User::find($leaderId);
            if ($leader) {
                $leader->notify(new \App\Notifications\RequestSubmittedNotification('klaim-lembur', $claim->id, $claimNumber, $user->name));
            }
        });

        return redirect()->route('riwayat-pengajuan.index')->with('success', 'Klaim lembur berhasil diajukan!');
    }
}

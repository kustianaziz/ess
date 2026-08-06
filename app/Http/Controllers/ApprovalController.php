<?php

namespace App\Http\Controllers;

use App\Actions\Shared\RecordStatusHistoryAction;
use App\Enums\RequestStatus;
use App\Models\Approval;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\OperationalRequest;
use App\Models\ReimbursementRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Query pending approvals matching current user & active level
        $pendingApprovals = Approval::with(['approvable.user.division', 'approvable.approvals.approver'])
            ->where('status', 'pending')
            ->where(function($q) use ($user) {
                if ($user->hasRole('admin')) {
                    return;
                }

                if ($user->hasRole('hrd_finance')) {
                    // HRD sees Level 2 pending approvals OR Level 1 where HRD is assigned explicitly
                    $q->where('level', 2)
                      ->orWhere(function($sub) use ($user) {
                          $sub->where('level', 1)->where('approver_id', $user->id);
                      });
                } else {
                    // Manager sees Level 1 pending approvals assigned to them
                    $q->where('approver_id', $user->id);
                }
            })
            ->latest()
            ->get();

        $seenRequests = [];

        $items = $pendingApprovals->map(function($approval) use (&$seenRequests) {
            $model = $approval->approvable;
            if (!$model) return null;

            // Only show if request is SUBMITTED and current_approval_level matches this approval's level
            if ($model->status->value !== RequestStatus::SUBMITTED->value) {
                return null;
            }

            if ($model->current_approval_level && (int)$model->current_approval_level !== (int)$approval->level) {
                return null;
            }

            // Ensure exactly 1 row per request
            $requestKey = get_class($model) . '_' . $model->id;
            if (in_array($requestKey, $seenRequests)) {
                return null;
            }
            $seenRequests[] = $requestKey;

            $type = match(get_class($model)) {
                ReimbursementRequest::class => 'reimbursement',
                OperationalRequest::class => 'operasional',
                LeaveRequest::class => 'cuti',
                \App\Models\BusinessTripRequest::class => 'perjalanan-dinas',
                default => 'unknown',
            };

            $typeLabel = match($type) {
                'reimbursement' => 'Reimbursement Karyawan',
                'operasional' => 'Konsumsi / Operasional',
                'cuti' => 'Cuti Karyawan',
                'perjalanan-dinas' => 'Perjalanan Dinas',
                default => 'Pengajuan',
            };

            $l1Approval = $model->approvals->where('level', 1)->first();
            $l2Approval = $model->approvals->where('level', 2)->first();

            return [
                'approval_id' => $approval->id,
                'level' => $approval->level,
                'type' => $type,
                'type_label' => $typeLabel,
                'id' => $model->id,
                'request_number' => $model->request_number,
                'applicant_name' => $model->user?->name ?? 'Karyawan',
                'applicant_division' => $model->user?->division?->name ?? '-',
                'submitted_at' => $model->submitted_at?->format('d M Y H:i') ?? '-',
                'l1_status' => $l1Approval ? $l1Approval->status : 'pending',
                'l1_approver' => $l1Approval?->approver?->name ?? 'Atasan',
                'l2_status' => $l2Approval ? $l2Approval->status : '-',
                'l2_approver' => $l2Approval?->approver?->name ?? 'HRD/Finance',
                'overall_status' => $model->status->value,
                'overall_status_label' => $model->status->label(),
            ];
        })->filter()->values();

        return Inertia::render('Approval/Index', [
            'pendingApprovals' => $items,
        ]);
    }

    public function approve(Request $request, string $type, int $id, RecordStatusHistoryAction $recordHistory): RedirectResponse
    {
        $user = $request->user();
        $notes = $request->input('notes', 'Pengajuan disetujui.');

        return DB::transaction(function() use ($type, $id, $user, $notes, $recordHistory) {
            $model = $this->getModel($type, $id);
            $currentLevel = $model->current_approval_level ?? 1;

            $approval = Approval::where('approvable_type', get_class($model))
                ->where('approvable_id', $model->id)
                ->where('level', $currentLevel)
                ->where('status', 'pending')
                ->firstOrFail();

            // Strict role restriction check
            if ($currentLevel === 1 && !$user->hasRole('admin')) {
                if ($user->hasRole('hrd_finance') && (int)$approval->approver_id !== (int)$user->id) {
                    return redirect()->route('approval.index')->with('error', 'Pengajuan ini masih menunggu persetujuan Atasan Langsung (Level 1).');
                }
            }

            // Update primary approval record
            $approval->update([
                'status' => 'approved',
                'notes' => $notes,
                'acted_at' => now(),
                'approver_id' => $user->id,
            ]);

            // Clean up any extra pending approval records for this request & level
            Approval::where('approvable_type', get_class($model))
                ->where('approvable_id', $model->id)
                ->where('level', $currentLevel)
                ->where('id', '!=', $approval->id)
                ->delete();

            if ($approval->level === 1) {
                // Fetch all HRD & Finance users
                $hrdUsers = User::role('hrd_finance')->get();
                if ($hrdUsers->isEmpty()) {
                    $hrdUsers = collect([$user]);
                }
                
                $firstHrd = $hrdUsers->first() ?? $user;
                
                // Create EXACTLY ONE Level 2 pending approval record (assigned to first HRD, open to all HRD)
                Approval::firstOrCreate([
                    'approvable_type' => get_class($model),
                    'approvable_id' => $model->id,
                    'level' => 2,
                ], [
                    'approver_id' => $firstHrd->id,
                    'status' => 'pending',
                ]);

                // Notify ALL HRD users
                foreach ($hrdUsers as $hrdUser) {
                    $hrdUser->notify(new \App\Notifications\RequestSubmittedNotification($type, $model->id, $model->request_number, $model->user?->name ?? 'Karyawan'));
                }

                $model->update(['current_approval_level' => 2]);
                $recordHistory->execute($model, RequestStatus::SUBMITTED->value, RequestStatus::SUBMITTED->value, $user->id, 'Disetujui oleh Atasan (Level 1). Diteruskan ke HRD/Finance.');

                // Notify applicant that Level 1 was approved
                $model->user?->notify(new \App\Notifications\RequestApprovedNotification($type, $model->id, $model->request_number, 1));
            } else {
                // Level 2 approval -> Mark request as APPROVED
                $oldStatus = $model->status->value;
                $model->update([
                    'status' => RequestStatus::APPROVED->value,
                    'current_approval_level' => 2,
                ]);

                // If Leave Request, update Leave Balance used/remaining
                if ($model instanceof LeaveRequest) {
                    $balance = LeaveBalance::where('user_id', $model->user_id)
                        ->where('leave_type_id', $model->leave_type_id)
                        ->where('year', date('Y', strtotime($model->start_date)))
                        ->first();

                    if ($balance) {
                        $balance->increment('used', $model->total_days);
                        $balance->decrement('remaining', $model->total_days);
                    }
                }

                $recordHistory->execute($model, $oldStatus, RequestStatus::APPROVED->value, $user->id, 'Pengajuan disetujui sepenuhnya (Level 2 Final).');

                // Notify applicant
                $model->user?->notify(new \App\Notifications\RequestApprovedNotification($type, $model->id, $model->request_number, 2));

                if (in_array($type, ['reimbursement', 'operasional', 'perjalanan-dinas']) && ($user->hasRole('admin') || $user->hasRole('hrd_finance'))) {
                    return redirect()->route('keuangan.pencairan.index')->with(
                        'success',
                        "Pengajuan {$model->request_number} berhasil disetujui! Pengajuan kini berada di antrean Pencairan & Pembayaran Kas."
                    );
                }
            }

            return redirect()->route('approval.index')->with('success', 'Pengajuan berhasil disetujui.');
        });
    }

    public function reject(Request $request, string $type, int $id, RecordStatusHistoryAction $recordHistory): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $reason = $request->input('reason');

        return DB::transaction(function() use ($type, $id, $user, $reason, $recordHistory) {
            $model = $this->getModel($type, $id);
            $currentLevel = $model->current_approval_level ?? 1;

            $approval = Approval::where('approvable_type', get_class($model))
                ->where('approvable_id', $model->id)
                ->where('level', $currentLevel)
                ->where('status', 'pending')
                ->first();

            if ($approval) {
                $approval->update([
                    'status' => 'rejected',
                    'notes' => $reason,
                    'acted_at' => now(),
                    'approver_id' => $user->id,
                ]);

                Approval::where('approvable_type', get_class($model))
                    ->where('approvable_id', $model->id)
                    ->where('level', $currentLevel)
                    ->where('id', '!=', $approval->id)
                    ->delete();
            }

            $oldStatus = $model->status->value;
            $model->update([
                'status' => RequestStatus::REJECTED->value,
                'rejected_reason' => $reason,
            ]);

            $recordHistory->execute($model, $oldStatus, RequestStatus::REJECTED->value, $user->id, "Pengajuan ditolak. Alasan: {$reason}");

            // Notify applicant
            $model->user?->notify(new \App\Notifications\RequestRejectedNotification($type, $model->id, $model->request_number, $reason));

            return redirect()->route('approval.index')->with('success', 'Pengajuan berhasil ditolak.');
        });
    }

    public function history(Request $request): Response
    {
        $user = $request->user();

        $query = Approval::with(['approvable.user.division', 'approver', 'approvable.approvals.approver'])
            ->whereIn('status', ['approved', 'rejected']);

        $scope = $request->input('scope', 'all');

        // Non-admin / non-HRD see only approvals acted by themselves
        if (!$user->hasRole('admin') && !$user->hasRole('hrd_finance')) {
            $query->where('approver_id', $user->id);
            $scope = 'my_approvals';
        } else if ($scope === 'my_approvals') {
            $query->where('approver_id', $user->id);
        }

        if ($request->filled('type') && $request->input('type') !== 'all') {
            $modelClass = match($request->input('type')) {
                'reimbursement' => ReimbursementRequest::class,
                'operasional' => OperationalRequest::class,
                'cuti' => LeaveRequest::class,
                'perjalanan-dinas' => \App\Models\BusinessTripRequest::class,
                default => null,
            };
            if ($modelClass) {
                $query->where('approvable_type', $modelClass);
            }
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('level') && $request->input('level') !== 'all') {
            $query->where('level', $request->input('level'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('acted_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('acted_at', '<=', $request->input('end_date'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->whereHasMorph('approvable', [ReimbursementRequest::class, OperationalRequest::class, LeaveRequest::class, \App\Models\BusinessTripRequest::class], function($mq) use ($search) {
                    $mq->where('request_number', 'like', "%{$search}%")
                       ->orWhereHas('user', function($uq) use ($search) {
                           $uq->where('name', 'like', "%{$search}%");
                       });
                });
            });
        }

        $historyApprovals = $query->latest('acted_at')->paginate(10)->withQueryString();

        $seenHistoryKeys = [];

        $transformedItems = $historyApprovals->getCollection()->map(function($approval) use ($user, &$seenHistoryKeys) {
            $model = $approval->approvable;
            if (!$model) return null;

            $historyKey = get_class($model) . '_' . $model->id;
            if (in_array($historyKey, $seenHistoryKeys)) {
                return null;
            }
            $seenHistoryKeys[] = $historyKey;

            $type = match(get_class($model)) {
                ReimbursementRequest::class => 'reimbursement',
                OperationalRequest::class => 'operasional',
                LeaveRequest::class => 'cuti',
                \App\Models\BusinessTripRequest::class => 'perjalanan-dinas',
                default => 'unknown',
            };

            $typeLabel = match($type) {
                'reimbursement' => 'Reimbursement',
                'operasional' => 'Konsumsi / Operasional',
                'cuti' => 'Cuti Karyawan',
                'perjalanan-dinas' => 'Perjalanan Dinas',
                default => 'Pengajuan',
            };

            $amountOrDays = match($type) {
                'reimbursement' => 'Rp ' . number_format($model->amount ?? 0, 0, ',', '.'),
                'operasional' => 'Rp ' . number_format($model->estimated_cost ?? 0, 0, ',', '.'),
                'cuti' => ($model->total_days ?? 0) . ' Hari',
                default => '-',
            };

            $l1Approval = $model->approvals->where('level', 1)->first();
            $l2Approval = $model->approvals->where('level', 2)->first();

            return [
                'approval_id' => $approval->id,
                'level' => $approval->level,
                'level_label' => $approval->level === 1 ? 'Level 1 (Atasan Langsung)' : 'Level 2 (HRD & Finance)',
                'acted_at' => $approval->acted_at?->format('d M Y H:i') ?? '-',
                'type' => $type,
                'type_label' => $typeLabel,
                'amount_or_days' => $amountOrDays,
                'id' => $model->id,
                'request_number' => $model->request_number,
                'applicant_name' => $model->user?->name ?? 'Karyawan',
                'applicant_division' => $model->user?->division?->name ?? '-',
                'approver_id' => $approval->approver_id,
                'is_acted_by_me' => (int)$approval->approver_id === (int)$user->id,
                'overall_status' => $model->status->value,
                'overall_status_label' => $model->status->label(),
                'l1_status' => $l1Approval ? $l1Approval->status : 'pending',
                'l1_approver' => $l1Approval?->approver?->name ?? 'Atasan',
                'l2_status' => $l2Approval ? $l2Approval->status : '-',
                'l2_approver' => $l2Approval?->approver?->name ?? '-',
            ];
        })->filter()->values();

        $historyApprovals->setCollection($transformedItems);

        return Inertia::render('Approval/History', [
            'approvals' => $historyApprovals,
            'filters' => array_merge(
                ['scope' => $scope],
                $request->only(['search', 'type', 'status', 'level', 'start_date', 'end_date'])
            ),
        ]);
    }

    public function unapprove(Request $request, string $type, int $id, RecordStatusHistoryAction $recordHistory): RedirectResponse
    {
        $user = $request->user();

        return DB::transaction(function() use ($type, $id, $user, $recordHistory) {
            $model = $this->getModel($type, $id);

            // Can only revert if status is NOT yet paid or completed
            if (in_array($model->status->value, ['paid', 'completed'])) {
                return back()->with('error', 'Transaksi yang telah dibayarkan / selesai tidak dapat dibatalkan persetujuannya.');
            }

            $currentLevel = $model->current_approval_level ?? 2;

            if ($currentLevel === 2 && $model->status->value === RequestStatus::APPROVED->value) {
                // Level 2 (Final HRD) was approved -> Revert Level 2 to pending
                $level2Approval = Approval::where('approvable_type', get_class($model))
                    ->where('approvable_id', $model->id)
                    ->where('level', 2)
                    ->where('status', 'approved')
                    ->first();

                if ($level2Approval) {
                    if ((int)$level2Approval->approver_id !== (int)$user->id && !$user->hasRole('admin') && !$user->hasRole('hrd_finance')) {
                        return back()->with('error', 'Hanya penyetuju Level 2 atau Admin yang dapat membatalkan persetujuan ini.');
                    }

                    $level2Approval->update([
                        'status' => 'pending',
                        'notes' => 'Persetujuan Level 2 dibatalkan oleh ' . $user->name,
                        'acted_at' => null,
                    ]);
                }

                // Refund leave balance if cuti
                if ($model instanceof LeaveRequest) {
                    $balance = LeaveBalance::where('user_id', $model->user_id)
                        ->where('leave_type_id', $model->leave_type_id)
                        ->where('year', date('Y', strtotime($model->start_date)))
                        ->first();

                    if ($balance) {
                        $balance->decrement('used', $model->total_days);
                        $balance->increment('remaining', $model->total_days);
                    }
                }

                $model->update([
                    'status' => RequestStatus::SUBMITTED->value,
                    'current_approval_level' => 2,
                ]);

                $recordHistory->execute($model, RequestStatus::APPROVED->value, RequestStatus::SUBMITTED->value, $user->id, 'Persetujuan Level 2 (Final) dibatalkan oleh ' . $user->name);

                return redirect()->route('approval.history')->with('success', "Persetujuan Level 2 untuk {$model->request_number} berhasil dibatalkan.");
            } 
            
            if ($currentLevel === 2 && $model->status->value === RequestStatus::SUBMITTED->value) {
                // Level 1 was approved, Level 2 is pending -> Revert Level 1 to pending, Delete Level 2 pending record!
                $level1Approval = Approval::where('approvable_type', get_class($model))
                    ->where('approvable_id', $model->id)
                    ->where('level', 1)
                    ->where('status', 'approved')
                    ->first();

                if ($level1Approval) {
                    if ((int)$level1Approval->approver_id !== (int)$user->id && !$user->hasRole('admin')) {
                        return back()->with('error', 'Hanya Atasan Penyetuju Level 1 atau Admin yang dapat membatalkan persetujuan Level 1 ini.');
                    }

                    // Delete Level 2 pending approval records
                    Approval::where('approvable_type', get_class($model))
                        ->where('approvable_id', $model->id)
                        ->where('level', 2)
                        ->delete();

                    $level1Approval->update([
                        'status' => 'pending',
                        'notes' => 'Persetujuan Level 1 dibatalkan oleh ' . $user->name,
                        'acted_at' => null,
                    ]);
                }

                $model->update([
                    'status' => RequestStatus::SUBMITTED->value,
                    'current_approval_level' => 1,
                ]);

                $recordHistory->execute($model, RequestStatus::SUBMITTED->value, RequestStatus::SUBMITTED->value, $user->id, 'Persetujuan Level 1 (Atasan) dibatalkan oleh ' . $user->name);

                return redirect()->route('approval.history')->with('success', "Persetujuan Level 1 untuk {$model->request_number} berhasil dibatalkan.");
            }

            return back()->with('error', 'Status pengajuan saat ini tidak dapat dibatalkan persetujuannya.');
        });
    }

    private function getModel(string $type, int $id)
    {
        return match($type) {
            'reimbursement' => ReimbursementRequest::findOrFail($id),
            'operasional' => OperationalRequest::findOrFail($id),
            'cuti' => LeaveRequest::findOrFail($id),
            'perjalanan-dinas' => \App\Models\BusinessTripRequest::findOrFail($id),
            default => abort(404),
        };
    }
}

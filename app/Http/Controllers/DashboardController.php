<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\LeaveRequest;
use App\Models\OperationalRequest;
use App\Models\ReimbursementRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user()->load(['division', 'manager']);

        $userId = $user->id;

        // Check if user is an approver or manager (has subordinates or role manager/admin/hrd)
        $isApprover = $user->hasRole('manager') || $user->hasRole('admin') || $user->hasRole('hrd_finance') || $user->subordinates()->exists();
        $approverDashboard = null;

        if ($isApprover) {
            $pendingApprovalsQuery = \App\Models\Approval::with([
                'approvable.user.division',
                'approvable.approvals.approver'
            ])
            ->where('status', 'pending');

            if ($user->hasRole('admin')) {
                // Admin sees all
            } elseif ($user->hasRole('hrd_finance')) {
                $pendingApprovalsQuery->where(function($q) use ($user) {
                    $q->whereIn('level', [2, 3])
                      ->orWhere(function($sub) use ($user) {
                          $sub->where('level', 1)->where('approver_id', $user->id);
                      });
                });
            } else {
                $pendingApprovalsQuery->where('approver_id', $user->id);
            }

            $pendingApprovals = $pendingApprovalsQuery->latest()->get();

            $seenRequests = [];
            $pendingItems = [];
            $totalPendingAmount = 0;
            $pendingByType = [
                'lembur' => 0,
                'klaim-lembur' => 0,
                'cuti' => 0,
                'reimbursement' => 0,
                'operasional' => 0,
                'perjalanan-dinas' => 0,
            ];

            foreach ($pendingApprovals as $approval) {
                $model = $approval->approvable;
                if (!$model) continue;
                if ($model->status->value !== RequestStatus::SUBMITTED->value) continue;
                if ($model->current_approval_level && (int)$model->current_approval_level !== (int)$approval->level) continue;

                $requestKey = get_class($model) . '_' . $model->id;
                if (in_array($requestKey, $seenRequests)) continue;
                $seenRequests[] = $requestKey;

                $type = match(get_class($model)) {
                    ReimbursementRequest::class => 'reimbursement',
                    OperationalRequest::class => 'operasional',
                    LeaveRequest::class => 'cuti',
                    \App\Models\BusinessTripRequest::class => 'perjalanan-dinas',
                    \App\Models\OvertimeRequest::class => 'lembur',
                    \App\Models\OvertimeClaim::class => 'klaim-lembur',
                    default => 'other',
                };

                if (isset($pendingByType[$type])) {
                    $pendingByType[$type]++;
                }

                $amount = $model->amount ?? $model->estimated_cost ?? $model->estimated_budget ?? 0;
                $totalPendingAmount += (float)$amount;

                $typeLabel = match($type) {
                    'reimbursement' => 'Reimbursement',
                    'operasional' => 'Operasional',
                    'cuti' => 'Cuti Karyawan',
                    'perjalanan-dinas' => 'Perjalanan Dinas',
                    'lembur' => 'Rencana Lembur',
                    'klaim-lembur' => 'Klaim Lembur',
                    default => 'Pengajuan',
                };

                $pendingItems[] = [
                    'approval_id' => $approval->id,
                    'level' => $approval->level,
                    'type' => $type,
                    'type_label' => $typeLabel,
                    'id' => $model->id,
                    'request_number' => $model->request_number ?? $model->claim_number,
                    'applicant_name' => $model->user?->name ?? 'Karyawan',
                    'applicant_avatar' => $model->user?->avatar,
                    'applicant_position' => $model->user?->position ?? 'Staff',
                    'applicant_division' => $model->user?->division?->name ?? '-',
                    'submitted_at' => $model->submitted_at?->translatedFormat('d M Y H:i') ?? $model->created_at?->translatedFormat('d M Y H:i'),
                    'amount' => (float)$amount,
                    'amount_formatted' => $amount > 0 ? 'Rp ' . number_format($amount, 0, ',', '.') : null,
                    'summary_info' => match($type) {
                        'cuti' => ($model->leaveType?->name ?? 'Cuti') . ' (' . $model->total_days . ' hari)',
                        'lembur' => 'Durasi: ' . ($model->duration ?? '-') . ' Jam',
                        'klaim-lembur' => 'Klaim: Rp ' . number_format($model->amount ?? 0, 0, ',', '.'),
                        'operasional' => $model->activity_name ?? 'Operasional',
                        'reimbursement' => $model->expenseType?->name ?? 'Reimbursement',
                        'perjalanan-dinas' => $model->destination ?? 'Perjalanan Dinas',
                        default => '-',
                    },
                    'url' => route('riwayat-pengajuan.show', ['type' => $type, 'id' => $model->id]) . '?from=approval',
                ];
            }

            $today = now()->toDateString();
            $subordinateIds = $user->subordinates()->pluck('id');

            $teamOnLeaveToday = LeaveRequest::with(['user:id,name,avatar,position', 'leaveType'])
                ->where('status', RequestStatus::APPROVED->value)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->when(!$user->hasRole('admin') && !$user->hasRole('hrd_finance'), function($q) use ($subordinateIds) {
                    $q->whereIn('user_id', $subordinateIds);
                })
                ->get()
                ->map(fn($item) => [
                    'name' => $item->user?->name,
                    'avatar' => $item->user?->avatar,
                    'position' => $item->user?->position,
                    'leave_type' => $item->leaveType?->name ?? 'Cuti',
                    'dates' => $item->start_date->format('d M') . ' - ' . $item->end_date->format('d M'),
                ]);

            $teamOvertimeToday = \App\Models\OvertimeRequest::with('user:id,name,avatar,position')
                ->where('status', RequestStatus::APPROVED->value)
                ->whereDate('date', $today)
                ->when(!$user->hasRole('admin') && !$user->hasRole('hrd_finance'), function($q) use ($subordinateIds) {
                    $q->whereIn('user_id', $subordinateIds);
                })
                ->get()
                ->map(fn($item) => [
                    'name' => $item->user?->name,
                    'avatar' => $item->user?->avatar,
                    'position' => $item->user?->position,
                    'hours' => $item->start_time . ' - ' . $item->end_time,
                    'task' => $item->task_description,
                ]);

            $startOfMonth = now()->startOfMonth();
            $approvedThisMonth = \App\Models\Approval::where('approver_id', $user->id)
                ->where('status', 'approved')
                ->where('acted_at', '>=', $startOfMonth)
                ->count();

            $approverDashboard = [
                'is_approver' => true,
                'pending_count' => count($pendingItems),
                'pending_amount' => $totalPendingAmount,
                'pending_amount_formatted' => 'Rp ' . number_format($totalPendingAmount, 0, ',', '.'),
                'pending_by_type' => $pendingByType,
                'pending_items' => array_slice($pendingItems, 0, 6),
                'approved_this_month' => $approvedThisMonth,
                'subordinates_count' => $subordinateIds->count(),
                'team_leave_today' => $teamOnLeaveToday,
                'team_overtime_today' => $teamOvertimeToday,
            ];
        }

        // Calculate summary counts for the authenticated user
        $counts = [
            'pending_approval' => ReimbursementRequest::where('user_id', $userId)->where('status', RequestStatus::SUBMITTED->value)->count()
                + OperationalRequest::where('user_id', $userId)->where('status', RequestStatus::SUBMITTED->value)->count()
                + LeaveRequest::where('user_id', $userId)->where('status', RequestStatus::SUBMITTED->value)->count()
                + \App\Models\OvertimeRequest::where('user_id', $userId)->where('status', RequestStatus::SUBMITTED->value)->count()
                + \App\Models\OvertimeClaim::where('user_id', $userId)->where('status', RequestStatus::SUBMITTED->value)->count()
                + \App\Models\BusinessTripRequest::where('user_id', $userId)->where('status', RequestStatus::SUBMITTED->value)->count(),

            'approved' => ReimbursementRequest::where('user_id', $userId)->where('status', RequestStatus::APPROVED->value)->count()
                + OperationalRequest::where('user_id', $userId)->where('status', RequestStatus::APPROVED->value)->count()
                + LeaveRequest::where('user_id', $userId)->where('status', RequestStatus::APPROVED->value)->count()
                + \App\Models\OvertimeRequest::where('user_id', $userId)->where('status', RequestStatus::APPROVED->value)->count()
                + \App\Models\OvertimeClaim::where('user_id', $userId)->where('status', RequestStatus::APPROVED->value)->count()
                + \App\Models\BusinessTripRequest::where('user_id', $userId)->where('status', RequestStatus::APPROVED->value)->count(),

            'rejected' => ReimbursementRequest::where('user_id', $userId)->where('status', RequestStatus::REJECTED->value)->count()
                + OperationalRequest::where('user_id', $userId)->where('status', RequestStatus::REJECTED->value)->count()
                + LeaveRequest::where('user_id', $userId)->where('status', RequestStatus::REJECTED->value)->count()
                + \App\Models\OvertimeRequest::where('user_id', $userId)->where('status', RequestStatus::REJECTED->value)->count()
                + \App\Models\OvertimeClaim::where('user_id', $userId)->where('status', RequestStatus::REJECTED->value)->count()
                + \App\Models\BusinessTripRequest::where('user_id', $userId)->where('status', RequestStatus::REJECTED->value)->count(),

            'paid' => ReimbursementRequest::where('user_id', $userId)->where('status', RequestStatus::PAID->value)->count()
                + OperationalRequest::where('user_id', $userId)->where('status', RequestStatus::PAID->value)->count()
                + \App\Models\OvertimeClaim::where('user_id', $userId)->where('status', RequestStatus::PAID->value)->count()
                + \App\Models\BusinessTripRequest::where('user_id', $userId)->where('status', RequestStatus::PAID->value)->count(),

            'completed' => ReimbursementRequest::where('user_id', $userId)->where('status', RequestStatus::COMPLETED->value)->count()
                + OperationalRequest::where('user_id', $userId)->where('status', RequestStatus::COMPLETED->value)->count()
                + LeaveRequest::where('user_id', $userId)->where('status', RequestStatus::COMPLETED->value)->count()
                + \App\Models\OvertimeRequest::where('user_id', $userId)->where('status', RequestStatus::COMPLETED->value)->count()
                + \App\Models\OvertimeClaim::where('user_id', $userId)->where('status', RequestStatus::COMPLETED->value)->count()
                + \App\Models\BusinessTripRequest::where('user_id', $userId)->where('status', RequestStatus::COMPLETED->value)->count(),
        ];

        return Inertia::render('Dashboard', [
            'user' => [
                'id' => $user->id,
                'nik' => $user->nik,
                'name' => $user->name,
                'email' => $user->email,
                'position' => $user->position,
                'avatar' => $user->avatar,
                'division' => $user->division?->name ?? 'N/A',
            ],
            'summaryCounts' => $counts,
            'recentRequests' => (function() use ($userId) {
                $recent = collect();
                foreach (\App\Models\ReimbursementRequest::with('expenseType')->where('user_id', $userId)->latest()->take(5)->get() as $item) {
                    $recent->push([
                        'id' => $item->id,
                        'type' => 'reimbursement',
                        'request_number' => $item->request_number,
                        'category' => $item->expenseType?->name ?? 'Reimbursement',
                        'date' => $item->expense_date?->format('M d') ?? $item->created_at->format('M d'),
                        'amount' => 'Rp ' . number_format($item->amount, 0, ',', '.'),
                        'status' => $item->status->value,
                        'status_label' => $item->status->label(),
                        'status_color' => $item->status->colorClass(),
                        'created_at' => $item->created_at,
                    ]);
                }
                foreach (\App\Models\OperationalRequest::with('activityType')->where('user_id', $userId)->latest()->take(5)->get() as $item) {
                    $recent->push([
                        'id' => $item->id,
                        'type' => 'operasional',
                        'request_number' => $item->request_number,
                        'category' => $item->activityType?->name ?? 'Operasional',
                        'date' => $item->activity_date?->format('M d') ?? $item->created_at->format('M d'),
                        'amount' => 'Rp ' . number_format($item->estimated_cost, 0, ',', '.'),
                        'status' => $item->status->value,
                        'status_label' => $item->status->label(),
                        'status_color' => $item->status->colorClass(),
                        'created_at' => $item->created_at,
                    ]);
                }
                foreach (\App\Models\LeaveRequest::with('leaveType')->where('user_id', $userId)->latest()->take(5)->get() as $item) {
                    $recent->push([
                        'id' => $item->id,
                        'type' => 'cuti',
                        'request_number' => $item->request_number,
                        'category' => $item->leaveType?->name ?? 'Cuti',
                        'date' => $item->start_date?->format('M d') ?? $item->created_at->format('M d'),
                        'amount' => $item->total_days . ' Hari',
                        'status' => $item->status->value,
                        'status_label' => $item->status->label(),
                        'status_color' => $item->status->colorClass(),
                        'created_at' => $item->created_at,
                    ]);
                }
                foreach (\App\Models\OvertimeRequest::where('user_id', $userId)->latest()->take(5)->get() as $item) {
                    $recent->push([
                        'id' => $item->id,
                        'type' => 'lembur',
                        'request_number' => $item->request_number,
                        'category' => 'Rencana Lembur',
                        'date' => $item->overtime_date?->format('M d') ?? $item->created_at->format('M d'),
                        'amount' => $item->duration . ' Jam',
                        'status' => $item->status->value,
                        'status_label' => $item->status->label(),
                        'status_color' => $item->status->colorClass(),
                        'created_at' => $item->created_at,
                    ]);
                }
                foreach (\App\Models\OvertimeClaim::where('user_id', $userId)->latest()->take(5)->get() as $item) {
                    $recent->push([
                        'id' => $item->id,
                        'type' => 'klaim-lembur',
                        'request_number' => $item->claim_number,
                        'category' => 'Klaim Lembur',
                        'date' => $item->created_at->format('M d'),
                        'amount' => 'Rp ' . number_format($item->amount, 0, ',', '.'),
                        'status' => $item->status->value,
                        'status_label' => $item->status->label(),
                        'status_color' => $item->status->colorClass(),
                        'created_at' => $item->created_at,
                    ]);
                }
                foreach (\App\Models\BusinessTripRequest::where('user_id', $userId)->latest()->take(5)->get() as $item) {
                    $recent->push([
                        'id' => $item->id,
                        'type' => 'perjalanan-dinas',
                        'request_number' => $item->request_number,
                        'category' => 'Perjalanan Dinas',
                        'date' => $item->start_date?->format('M d') ?? $item->created_at->format('M d'),
                        'amount' => $item->total_days . ' Hari',
                        'status' => $item->status->value,
                        'status_label' => $item->status->label(),
                        'status_color' => $item->status->colorClass(),
                        'created_at' => $item->created_at,
                    ]);
                }
                return $recent->sortByDesc('created_at')->take(5)->values();
            })(),
            'isApprover' => $isApprover,
            'approverDashboard' => $approverDashboard,
        ]);
    }
}

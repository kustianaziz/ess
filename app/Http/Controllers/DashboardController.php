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

        // Calculate summary counts for the authenticated user
        $counts = [
            'pending_approval' => ReimbursementRequest::where('user_id', $userId)->where('status', RequestStatus::SUBMITTED->value)->count()
                + OperationalRequest::where('user_id', $userId)->where('status', RequestStatus::SUBMITTED->value)->count()
                + LeaveRequest::where('user_id', $userId)->where('status', RequestStatus::SUBMITTED->value)->count(),

            'approved' => ReimbursementRequest::where('user_id', $userId)->where('status', RequestStatus::APPROVED->value)->count()
                + OperationalRequest::where('user_id', $userId)->where('status', RequestStatus::APPROVED->value)->count()
                + LeaveRequest::where('user_id', $userId)->where('status', RequestStatus::APPROVED->value)->count(),

            'rejected' => ReimbursementRequest::where('user_id', $userId)->where('status', RequestStatus::REJECTED->value)->count()
                + OperationalRequest::where('user_id', $userId)->where('status', RequestStatus::REJECTED->value)->count()
                + LeaveRequest::where('user_id', $userId)->where('status', RequestStatus::REJECTED->value)->count(),

            'paid' => ReimbursementRequest::where('user_id', $userId)->where('status', RequestStatus::PAID->value)->count()
                + OperationalRequest::where('user_id', $userId)->where('status', RequestStatus::PAID->value)->count(),

            'completed' => ReimbursementRequest::where('user_id', $userId)->where('status', RequestStatus::COMPLETED->value)->count()
                + OperationalRequest::where('user_id', $userId)->where('status', RequestStatus::COMPLETED->value)->count()
                + LeaveRequest::where('user_id', $userId)->where('status', RequestStatus::COMPLETED->value)->count(),
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
                return $recent->sortByDesc('created_at')->take(5)->values();
            })(),
        ]);
    }
}

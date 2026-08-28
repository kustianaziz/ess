<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\BusinessTripRequest;
use App\Models\CashAccount;
use App\Models\OperationalRequest;
use App\Models\ReimbursementRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceDisbursementController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Fetch Reimbursement Requests
        $reimbursements = ReimbursementRequest::with(['user.division', 'expenseType', 'paidBy', 'attachments'])
            ->whereIn('status', [RequestStatus::APPROVED->value, RequestStatus::PAID->value, RequestStatus::COMPLETED->value])
            ->latest()
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'type' => 'reimbursement',
                'type_label' => 'Reimbursement',
                'request_number' => $item->request_number,
                'applicant_name' => $item->user->name,
                'division' => $item->user->division?->name ?? '-',
                'category' => $item->expenseType?->name ?? 'Pengeluaran',
                'amount' => (float)$item->amount,
                'amount_formatted' => 'Rp ' . number_format($item->amount, 0, ',', '.'),
                'created_at' => $item->created_at->translatedFormat('d M Y H:i'),
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'status_color' => $item->status->colorClass(),
                'payment_reference' => $item->payment_reference,
                'paid_at' => $item->paid_at ? $item->paid_at->translatedFormat('d M Y H:i') : null,
                'paid_by_name' => $item->paidBy?->name ?? '-',
            ]);

        // 2. Fetch Operational Requests
        $operationals = OperationalRequest::with(['user.division', 'activityType', 'paidBy', 'attachments'])
            ->whereIn('status', [RequestStatus::APPROVED->value, RequestStatus::PAID->value, RequestStatus::COMPLETED->value])
            ->latest()
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'type' => 'operasional',
                'type_label' => 'Konsumsi / Operasional',
                'request_number' => $item->request_number,
                'applicant_name' => $item->user->name,
                'division' => $item->user->division?->name ?? '-',
                'category' => $item->activityType?->name ?? $item->activity_name,
                'amount' => (float)$item->estimated_cost,
                'amount_formatted' => 'Rp ' . number_format($item->estimated_cost, 0, ',', '.'),
                'created_at' => $item->created_at->translatedFormat('d M Y H:i'),
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'status_color' => $item->status->colorClass(),
                'payment_reference' => $item->payment_reference,
                'paid_at' => $item->paid_at ? $item->paid_at->translatedFormat('d M Y H:i') : null,
                'paid_by_name' => $item->paidBy?->name ?? '-',
            ]);

        // 3. Fetch Business Trip Requests
        $businessTrips = BusinessTripRequest::with(['user.division', 'paidBy', 'attachments'])
            ->whereIn('status', [RequestStatus::APPROVED->value, RequestStatus::PAID->value, RequestStatus::COMPLETED->value])
            ->latest()
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'type' => 'perjalanan-dinas',
                'type_label' => 'Perjalanan Dinas',
                'request_number' => $item->request_number,
                'applicant_name' => $item->user->name,
                'division' => $item->user->division?->name ?? '-',
                'category' => 'Dinas Ke ' . ($item->destination_instance ?? $item->destination_city ?? 'Tujuan'),
                'amount' => (float)($item->disbursed_budget ?? $item->estimated_budget),
                'amount_formatted' => 'Rp ' . number_format($item->disbursed_budget ?? $item->estimated_budget, 0, ',', '.'),
                'created_at' => $item->created_at->translatedFormat('d M Y H:i'),
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'status_color' => $item->status->colorClass(),
                'payment_reference' => $item->payment_reference,
                'paid_at' => $item->paid_at ? $item->paid_at->translatedFormat('d M Y H:i') : null,
                'paid_by_name' => $item->paidBy?->name ?? '-',
            ]);

        // 4. Fetch Overtime Claims
        $overtimeClaims = \App\Models\OvertimeClaim::with(['user.division', 'paidBy', 'attachments'])
            ->whereIn('status', [RequestStatus::APPROVED->value, RequestStatus::PAID->value, RequestStatus::COMPLETED->value])
            ->latest()
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'type' => 'klaim-lembur',
                'type_label' => 'Klaim Lembur',
                'request_number' => $item->claim_number,
                'applicant_name' => $item->user->name,
                'division' => $item->user->division?->name ?? '-',
                'category' => 'Uang Lembur',
                'amount' => (float)$item->amount,
                'amount_formatted' => 'Rp ' . number_format($item->amount, 0, ',', '.'),
                'created_at' => $item->created_at->translatedFormat('d M Y H:i'),
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'status_color' => $item->status->colorClass(),
                'payment_reference' => $item->payment_reference,
                'paid_at' => $item->paid_at ? $item->paid_at->translatedFormat('d M Y H:i') : null,
                'paid_by_name' => $item->paidBy?->name ?? '-',
            ]);

        // Merge all items
        $allItems = $reimbursements->concat($operationals)->concat($businessTrips)->concat($overtimeClaims)->sortByDesc('created_at')->values();

        $unpaidItems = $allItems->whereNotIn('status', ['paid', 'completed'])->values();
        $paidItems = $allItems->whereIn('status', ['paid', 'completed'])->values();

        // Cash Accounts
        $cashAccounts = CashAccount::where('is_active', true)
            ->get()
            ->map(fn($acc) => [
                'id' => $acc->id,
                'name' => $acc->name,
                'code' => $acc->code,
                'type' => $acc->type,
                'type_label' => ($acc->type === 'bank') ? 'Rekening Bank' : 'Kas Tunai',
                'bank_name' => $acc->bank_name,
                'account_number' => $acc->account_number,
                'current_balance_formatted' => 'Rp ' . number_format($acc->current_balance, 0, ',', '.'),
            ]);

        return Inertia::render('Keuangan/Pencairan/Index', [
            'unpaidItems' => $unpaidItems,
            'paidItems' => $paidItems,
            'cashAccounts' => $cashAccounts,
            'summary' => [
                'unpaid_count' => $unpaidItems->count(),
                'unpaid_total' => $unpaidItems->sum('amount'),
                'unpaid_total_formatted' => 'Rp ' . number_format($unpaidItems->sum('amount'), 0, ',', '.'),
                'paid_count' => $paidItems->count(),
                'paid_total' => $paidItems->sum('amount'),
                'paid_total_formatted' => 'Rp ' . number_format($paidItems->sum('amount'), 0, ',', '.'),
            ],
        ]);
    }
}

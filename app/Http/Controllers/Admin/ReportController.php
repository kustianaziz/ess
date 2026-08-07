<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\LeaveRequest;
use App\Models\OperationalRequest;
use App\Models\ReimbursementRequest;
use App\Models\BusinessTripRequest;
use App\Models\MonthlyBillPayment;
use App\Models\RenewalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $data = $this->getReportData($request);

        return Inertia::render('Admin/Reports/Index', [
            'stats' => $data['stats'],
            'divisionSummary' => $data['divisionSummary'],
            'detailList' => $data['detailList'],
            'divisions' => Division::select('id', 'name')->get(),
            'filters' => [
                'start_date' => $request->input('start_date', ''),
                'end_date' => $request->input('end_date', ''),
                'type' => $request->input('type', 'all'),
                'status' => $request->input('status', 'all'),
                'division_id' => $request->input('division_id', 'all'),
                'mode' => $request->input('mode', 'all'), // 'all', 'list', 'rekap'
            ],
        ]);
    }

    public function exportExcel(Request $request)
    {
        $data = $this->getReportData($request);
        $filename = 'Laporan_ESS_' . date('Ymd_His') . '.xls';

        $html = view('exports.reports_excel', $data)->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportWord(Request $request)
    {
        $data = $this->getReportData($request);
        $filename = 'Laporan_ESS_' . date('Ymd_His') . '.doc';

        $html = view('exports.reports_word', $data)->render();

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function getReportData(Request $request): array
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $type = $request->input('type', 'all');
        $status = $request->input('status', 'all');
        $divisionId = $request->input('division_id', 'all');

        $reimbursements = collect();
        $operationals = collect();
        $leaves = collect();
        $businessTrips = collect();
        $monthlyBills = collect();
        $renewals = collect();

        // 1. Query Reimbursements
        if ($type === 'all' || $type === 'reimbursement') {
            $q = ReimbursementRequest::with(['user.division', 'expenseType']);
            if ($startDate) $q->whereDate('created_at', '>=', $startDate);
            if ($endDate) $q->whereDate('created_at', '<=', $endDate);
            if ($status !== 'all') $q->where('status', $status);
            if ($divisionId !== 'all') {
                $q->whereHas('user', fn($uq) => $uq->where('division_id', $divisionId));
            }
            $reimbursements = $q->latest()->get();
        }

        // 2. Query Operationals
        if ($type === 'all' || $type === 'operasional') {
            $q = OperationalRequest::with(['user.division', 'activityType']);
            if ($startDate) $q->whereDate('created_at', '>=', $startDate);
            if ($endDate) $q->whereDate('created_at', '<=', $endDate);
            if ($status !== 'all') $q->where('status', $status);
            if ($divisionId !== 'all') {
                $q->whereHas('user', fn($uq) => $uq->where('division_id', $divisionId));
            }
            $operationals = $q->latest()->get();
        }

        // 3. Query Leaves
        if ($type === 'all' || $type === 'cuti') {
            $q = LeaveRequest::with(['user.division', 'leaveType']);
            if ($startDate) $q->whereDate('created_at', '>=', $startDate);
            if ($endDate) $q->whereDate('created_at', '<=', $endDate);
            if ($status !== 'all') $q->where('status', $status);
            if ($divisionId !== 'all') {
                $q->whereHas('user', fn($uq) => $uq->where('division_id', $divisionId));
            }
            $leaves = $q->latest()->get();
        }

        // 4. Query Business Trips
        if ($type === 'all' || $type === 'perjalanan-dinas') {
            $q = BusinessTripRequest::with(['user.division']);
            if ($startDate) $q->whereDate('created_at', '>=', $startDate);
            if ($endDate) $q->whereDate('created_at', '<=', $endDate);
            if ($status !== 'all') $q->where('status', $status);
            if ($divisionId !== 'all') {
                $q->whereHas('user', fn($uq) => $uq->where('division_id', $divisionId));
            }
            $businessTrips = $q->latest()->get();
        }

        // 5. Query Monthly Bills
        if ($type === 'all' || $type === 'tagihan-bulanan') {
            $q = MonthlyBillPayment::with(['billType', 'paidBy.division']);
            if ($startDate) $q->whereDate('created_at', '>=', $startDate);
            if ($endDate) $q->whereDate('created_at', '<=', $endDate);
            if ($status !== 'all') $q->where('status', $status);
            // Monthly bills might not have a specific division if paid by system/finance, but we can filter by paid_by if needed
            if ($divisionId !== 'all') {
                $q->whereHas('paidBy', fn($uq) => $uq->where('division_id', $divisionId));
            }
            $monthlyBills = $q->latest()->get();
        }

        // 6. Query Renewals
        if ($type === 'all' || $type === 'renewal-domain') {
            $q = RenewalRequest::with(['domain', 'processor.division']);
            if ($startDate) $q->whereDate('created_at', '>=', $startDate);
            if ($endDate) $q->whereDate('created_at', '<=', $endDate);
            // Renewal status doesn't match generic status exactly, but we can filter
            if ($status !== 'all') {
                if ($status === 'approved' || $status === 'paid' || $status === 'completed') {
                    $q->whereIn('status', ['paid_customer', 'paid_vendor', 'completed']);
                } else if ($status === 'rejected') {
                    $q->where('status', 'cancelled');
                } else {
                    $q->where('status', 'pending');
                }
            }
            if ($divisionId !== 'all') {
                $q->whereHas('processor', fn($uq) => $uq->where('division_id', $divisionId));
            }
            $renewals = $q->latest()->get();
        }

        // Combined detail list
        $detailList = collect();

        foreach ($reimbursements as $item) {
            $detailList->push([
                'id' => $item->id,
                'request_number' => $item->request_number,
                'type' => 'reimbursement',
                'type_label' => 'Reimbursement',
                'category' => $item->expenseType?->name ?? 'Pengeluaran',
                'applicant_name' => $item->user?->name ?? 'Karyawan',
                'applicant_nik' => $item->user?->nik ?? '-',
                'division_name' => $item->user?->division?->name ?? '-',
                'date' => $item->expense_date?->format('d/m/Y') ?? $item->created_at->format('d/m/Y'),
                'amount_formatted' => 'Rp ' . number_format($item->amount, 0, ',', '.'),
                'amount_raw' => $item->amount,
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'created_at' => $item->created_at->format('d/m/Y H:i'),
            ]);
        }

        foreach ($operationals as $item) {
            $detailList->push([
                'id' => $item->id,
                'request_number' => $item->request_number,
                'type' => 'operasional',
                'type_label' => 'Operasional / Konsumsi',
                'category' => $item->activityType?->name ?? 'Kegiatan',
                'applicant_name' => $item->user?->name ?? 'Karyawan',
                'applicant_nik' => $item->user?->nik ?? '-',
                'division_name' => $item->user?->division?->name ?? '-',
                'date' => $item->activity_date?->format('d/m/Y') ?? $item->created_at->format('d/m/Y'),
                'amount_formatted' => 'Rp ' . number_format($item->estimated_cost, 0, ',', '.'),
                'amount_raw' => $item->estimated_cost,
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'created_at' => $item->created_at->format('d/m/Y H:i'),
            ]);
        }

        foreach ($leaves as $item) {
            $detailList->push([
                'id' => $item->id,
                'request_number' => $item->request_number,
                'type' => 'cuti',
                'type_label' => 'Cuti Karyawan',
                'category' => $item->leaveType?->name ?? 'Cuti',
                'applicant_name' => $item->user?->name ?? 'Karyawan',
                'applicant_nik' => $item->user?->nik ?? '-',
                'division_name' => $item->user?->division?->name ?? '-',
                'date' => $item->start_date?->format('d/m/Y') ?? $item->created_at->format('d/m/Y'),
                'amount_formatted' => $item->total_days . ' Hari',
                'amount_raw' => $item->total_days,
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'created_at' => $item->created_at->format('d/m/Y H:i'),
            ]);
        }

        foreach ($businessTrips as $item) {
            $detailList->push([
                'id' => $item->id,
                'request_number' => $item->request_number,
                'type' => 'perjalanan-dinas',
                'type_label' => 'Perjalanan Dinas',
                'category' => 'Dinas',
                'applicant_name' => $item->user?->name ?? 'Karyawan',
                'applicant_nik' => $item->user?->nik ?? '-',
                'division_name' => $item->user?->division?->name ?? '-',
                'date' => $item->departure_date?->format('d/m/Y') ?? $item->created_at->format('d/m/Y'),
                'amount_formatted' => 'Rp ' . number_format($item->disbursed_budget ?? $item->estimated_budget ?? 0, 0, ',', '.'),
                'amount_raw' => $item->disbursed_budget ?? $item->estimated_budget ?? 0,
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'created_at' => $item->created_at->format('d/m/Y H:i'),
            ]);
        }

        foreach ($monthlyBills as $item) {
            $detailList->push([
                'id' => $item->id,
                'request_number' => $item->payment_number,
                'type' => 'tagihan-bulanan',
                'type_label' => 'Tagihan Bulanan',
                'category' => $item->billType?->name ?? 'Tagihan',
                'applicant_name' => $item->paidBy?->name ?? 'Sistem / Finance',
                'applicant_nik' => $item->paidBy?->nik ?? '-',
                'division_name' => $item->paidBy?->division?->name ?? 'Keuangan',
                'date' => $item->due_date?->format('d/m/Y') ?? $item->created_at->format('d/m/Y'),
                'amount_formatted' => 'Rp ' . number_format($item->bill_amount ?? 0, 0, ',', '.'),
                'amount_raw' => $item->bill_amount ?? 0,
                'status' => $item->status,
                'status_label' => ucfirst($item->status),
                'created_at' => $item->created_at->format('d/m/Y H:i'),
            ]);
        }

        foreach ($renewals as $item) {
            $detailList->push([
                'id' => $item->id,
                'request_number' => $item->renewal_number,
                'type' => 'renewal-domain',
                'type_label' => 'Renewal Domain/Hosting',
                'category' => $item->domain?->type ?? 'Domain/Hosting',
                'applicant_name' => $item->processor?->name ?? 'Admin',
                'applicant_nik' => $item->processor?->nik ?? '-',
                'division_name' => $item->processor?->division?->name ?? 'IT',
                'date' => $item->created_at->format('d/m/Y'),
                'amount_formatted' => '-',
                'amount_raw' => 0,
                'status' => $item->status,
                'status_label' => str_replace('_', ' ', ucfirst($item->status)),
                'created_at' => $item->created_at->format('d/m/Y H:i'),
            ]);
        }

        // Summary Stats
        $stats = [
            'total_reimbursement_count' => $reimbursements->count(),
            'total_reimbursement_amount' => $reimbursements->whereIn('status.value', ['approved', 'paid', 'completed'])->sum('amount'),
            
            'total_operational_count' => $operationals->count(),
            'total_operational_amount' => $operationals->whereIn('status.value', ['approved', 'paid', 'completed'])->sum('estimated_cost'),
            
            'total_leave_count' => $leaves->count(),
            'total_leave_days' => $leaves->whereIn('status.value', ['approved', 'completed'])->sum('total_days'),

            'total_business_trip_count' => $businessTrips->count(),
            'total_business_trip_amount' => $businessTrips->whereIn('status.value', ['approved', 'paid', 'completed'])->sum(fn($bt) => $bt->disbursed_budget ?? $bt->estimated_budget ?? 0),

            'total_monthly_bill_count' => $monthlyBills->count(),
            'total_monthly_bill_amount' => $monthlyBills->where('status', 'paid')->sum('bill_amount'),

            'total_renewal_count' => $renewals->count(),

            'grand_total_count' => $detailList->count(),
        ];

        // Division Summary Rekap
        $divisionSummary = $detailList->groupBy('division_name')->map(function($items, $divName) {
            return [
                'division_name' => $divName,
                'reimbursement_count' => $items->where('type', 'reimbursement')->count(),
                'reimbursement_sum' => $items->where('type', 'reimbursement')->whereIn('status', ['approved', 'paid', 'completed'])->sum('amount_raw'),
                'operational_count' => $items->where('type', 'operasional')->count(),
                'operational_sum' => $items->where('type', 'operasional')->whereIn('status', ['approved', 'paid', 'completed'])->sum('amount_raw'),
                'leave_count' => $items->where('type', 'cuti')->count(),
                'leave_days_sum' => $items->where('type', 'cuti')->whereIn('status', ['approved', 'completed'])->sum('amount_raw'),
                'business_trip_count' => $items->where('type', 'perjalanan-dinas')->count(),
                'business_trip_sum' => $items->where('type', 'perjalanan-dinas')->whereIn('status', ['approved', 'paid', 'completed'])->sum('amount_raw'),
                'monthly_bill_count' => $items->where('type', 'tagihan-bulanan')->count(),
                'monthly_bill_sum' => $items->where('type', 'tagihan-bulanan')->where('status', 'paid')->sum('amount_raw'),
                'renewal_count' => $items->where('type', 'renewal-domain')->count(),
                'total_requests' => $items->count(),
            ];
        })->values();

        return [
            'stats' => $stats,
            'divisionSummary' => $divisionSummary,
            'detailList' => $detailList->sortByDesc('created_at')->values(),
            'period_label' => ($startDate ? "Dari {$startDate}" : 'Awal') . ' s/d ' . ($endDate ? $endDate : 'Sekarang'),
            'generated_at' => now()->format('d F Y H:i'),
        ];
    }
}

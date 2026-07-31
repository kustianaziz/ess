<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\LeaveRequest;
use App\Models\OperationalRequest;
use App\Models\ReimbursementRequest;
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

        // Summary Stats
        $stats = [
            'total_reimbursement_count' => $reimbursements->count(),
            'total_reimbursement_amount' => $reimbursements->whereIn('status.value', ['approved', 'paid', 'completed'])->sum('amount'),
            
            'total_operational_count' => $operationals->count(),
            'total_operational_amount' => $operationals->whereIn('status.value', ['approved', 'paid', 'completed'])->sum('estimated_cost'),
            
            'total_leave_count' => $leaves->count(),
            'total_leave_days' => $leaves->whereIn('status.value', ['approved', 'completed'])->sum('total_days'),

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

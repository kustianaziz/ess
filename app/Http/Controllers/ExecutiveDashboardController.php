<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\InvoicePayment;
use App\Models\CashTransaction;
use App\Models\VendorPayment;
use App\Models\ReimbursementRequest;
use App\Models\OperationalRequest;

class ExecutiveDashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->query('period', 'monthly'); // daily, weekly, monthly

        // Financial KPIs
        $revenueTotal = InvoicePayment::sum('amount') + CashTransaction::where('type', 'in')->where('category', '!=', 'mutasi')->whereNull('source_type')->sum('amount');
        $piutangTotal = \App\Models\Invoice::whereNotIn('status', ['paid', 'cancelled'])->get()->sum(function($inv) {
            return $inv->total_amount - $inv->paid_amount;
        });
        
        $expenseTotal = VendorPayment::sum('amount') 
            + ReimbursementRequest::whereIn('status', ['paid', 'completed'])->sum('amount')
            + OperationalRequest::whereIn('status', ['paid', 'completed'])->sum('estimated_cost')
            + \App\Models\BusinessTripRequest::whereIn('status', ['paid', 'completed'])->sum('disbursed_budget')
            + CashTransaction::where('type', 'out')->where('category', '!=', 'mutasi')->whereNull('source_type')->sum('amount');

        // Category Breakdown
        $defaultExpenseCats = [
            'Vendor Renewal' => 0,
            'Reimbursement' => 0,
            'Operasional' => 0,
            'Perjalanan Dinas' => 0,
            'Pembayaran Bulanan' => 0
        ];
        $defaultExpenseCats['Vendor Renewal'] = (float)\App\Models\VendorPayment::sum('amount');
        $defaultExpenseCats['Reimbursement'] = (float)\App\Models\ReimbursementRequest::whereIn('status', ['paid', 'completed'])->sum('amount');
        $defaultExpenseCats['Operasional'] = (float)\App\Models\OperationalRequest::whereIn('status', ['paid', 'completed'])->sum('estimated_cost');
        $defaultExpenseCats['Perjalanan Dinas'] = (float)\App\Models\BusinessTripRequest::whereIn('status', ['paid', 'completed'])->sum('disbursed_budget');
        $defaultExpenseCats['Pembayaran Bulanan'] = (float)CashTransaction::where('type', 'out')->where('category', '!=', 'mutasi')->whereNull('source_type')->sum('amount');

        $expenseByCategory = [];
        foreach ($defaultExpenseCats as $k => $v) {
            $expenseByCategory[] = ['category' => $k, 'total' => $v];
        }

        $defaultRevCats = [
            'Invoicing Umum' => 0,
            'Renewal Webpraktis' => 0
        ];

        $revenueData = InvoicePayment::join('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id')
            ->selectRaw('invoices.source_type as category, sum(invoice_payments.amount) as total')
            ->groupBy('invoices.source_type')
            ->get();
        
        foreach ($revenueData as $rev) {
            $cat = $rev->category == 'renewal' ? 'Renewal Webpraktis' : 'Invoicing Umum';
            $defaultRevCats[$cat] = (float)$rev->total;
        }

        $cashInTrans = CashTransaction::where('type', 'in')->where('category', '!=', 'mutasi')->whereNull('source_type')->sum('amount');
        $defaultRevCats['Transaksi Lainnya'] = (float)$cashInTrans;

        $revenueByCategory = [];
        foreach ($defaultRevCats as $k => $v) {
            $revenueByCategory[] = ['category' => $k, 'total' => $v];
        }
        
        // Chart Data (Revenue vs Expense)
        $chartData = $this->getChartData($period);

        return Inertia::render('Executive/Dashboard', [
            'revenue_total' => $revenueTotal,
            'piutang_total' => $piutangTotal,
            'expense_total' => $expenseTotal,
            'net_profit' => $revenueTotal - $expenseTotal,
            'chart_data' => $chartData,
            'revenue_by_category' => $revenueByCategory,
            'expense_by_category' => $expenseByCategory,
            'period' => $period,
            'top_customers' => \App\Models\Customer::withSum('invoices as total_revenue', 'total_amount')
                ->with(['invoices' => function($q) {
                    $q->latest('invoice_date')->take(1);
                }])
                ->orderByDesc('total_revenue')
                ->take(5)
                ->get(),
            'active_domains' => \App\Models\Domain::where('status', 'active')->count(),
            'renewal_margin' => \App\Models\Domain::where('status', 'active')->get()->sum(function($d) {
                return $d->price_customer - $d->cost_vendor;
            }),
            'upcoming_renewals' => \App\Models\Domain::with('customer')
                ->where('status', 'active')
                ->orderBy('expired_date', 'asc')
                ->take(5)
                ->get(),
            'assets_by_category' => \App\Models\Asset::join('coas', 'assets.coa_asset_id', '=', 'coas.id')
                ->selectRaw('coas.name as category, sum(assets.book_value) as total')
                ->groupBy('coas.name')
                ->get(),
            'leaves_by_month' => $this->getLeavesByMonth(),
            'detailed_expense_breakdown' => $this->getDetailedExpenseBreakdown(),
        ]);
    }

    private function getDetailedExpenseBreakdown(): array
    {
        // 1. Reimbursement per ExpenseType
        $expenseTypes = \App\Models\ExpenseType::all();
        $reimbursementData = [];
        $totalReimbPaid = 0;
        $totalReimbAll = 0;
        $aiTotalAmount = 0;
        $aiTotalCount = 0;

        foreach ($expenseTypes as $type) {
            $requests = \App\Models\ReimbursementRequest::with('user:id,name,avatar,position')
                ->where('expense_type_id', $type->id)
                ->latest()
                ->get();

            if ($requests->isEmpty()) continue;

            $paidTotal = (float)$requests->filter(fn($r) => in_array($r->status->value, ['paid', 'completed']))->sum('amount');
            $pendingTotal = (float)$requests->filter(fn($r) => in_array($r->status->value, ['submitted', 'approved', 'level_1_approved']))->sum('amount');
            $total = (float)$requests->sum('amount');
            $isAi = str_contains(strtolower($type->name), 'ai');

            if ($isAi) {
                $aiTotalAmount += $total;
                $aiTotalCount += $requests->count();
            }

            $totalReimbPaid += $paidTotal;
            $totalReimbAll += $total;

            $reimbursementData[] = [
                'id' => $type->id,
                'name' => $type->name,
                'is_ai' => $isAi,
                'type' => 'reimbursement',
                'count' => $requests->count(),
                'paid_total' => $paidTotal,
                'pending_total' => $pendingTotal,
                'total' => $total,
                'items' => $requests->map(fn($r) => [
                    'id' => $r->id,
                    'request_number' => $r->request_number,
                    'applicant' => $r->user?->name ?? 'Karyawan',
                    'amount' => (float)$r->amount,
                    'description' => $r->description,
                    'status' => $r->status->value,
                    'status_label' => $r->status->label(),
                    'date' => $r->expense_date?->format('d M Y') ?? $r->created_at->format('d M Y'),
                ])->values()->all(),
            ];
        }

        usort($reimbursementData, fn($a, $b) => $b['total'] <=> $a['total']);

        // 2. Operational per ActivityType
        $activityTypes = \App\Models\ActivityType::all();
        $operationalData = [];
        $totalOpsPaid = 0;
        $totalOpsAll = 0;

        foreach ($activityTypes as $act) {
            $requests = \App\Models\OperationalRequest::with('user:id,name,avatar,position')
                ->where('activity_type_id', $act->id)
                ->latest()
                ->get();

            if ($requests->isEmpty()) continue;

            $paidTotal = (float)$requests->filter(fn($r) => in_array($r->status->value, ['paid', 'completed']))->sum('estimated_cost');
            $pendingTotal = (float)$requests->filter(fn($r) => in_array($r->status->value, ['submitted', 'approved', 'level_1_approved']))->sum('estimated_cost');
            $total = (float)$requests->sum('estimated_cost');

            $totalOpsPaid += $paidTotal;
            $totalOpsAll += $total;

            $operationalData[] = [
                'id' => $act->id,
                'name' => $act->name,
                'type' => 'operational',
                'count' => $requests->count(),
                'paid_total' => $paidTotal,
                'pending_total' => $pendingTotal,
                'total' => $total,
                'items' => $requests->map(fn($r) => [
                    'id' => $r->id,
                    'request_number' => $r->request_number,
                    'applicant' => $r->user?->name ?? 'Karyawan',
                    'amount' => (float)$r->estimated_cost,
                    'description' => $r->activity_name . ($r->purpose ? ' - ' . $r->purpose : ''),
                    'status' => $r->status->value,
                    'status_label' => $r->status->label(),
                    'date' => $r->activity_date?->format('d M Y') ?? $r->created_at->format('d M Y'),
                ])->values()->all(),
            ];
        }

        usort($operationalData, fn($a, $b) => $b['total'] <=> $a['total']);

        // 3. Business Trips Breakdown
        $trips = \App\Models\BusinessTripRequest::with('user:id,name,avatar,position')->latest()->get();
        $tripComponents = [];
        $totalTripPaid = 0;
        $totalTripAll = 0;

        foreach ($trips as $t) {
            $budget = (float)($t->disbursed_budget ?? $t->estimated_budget ?? 0);
            $isPaid = in_array($t->status->value, ['paid', 'completed']);
            if ($isPaid) $totalTripPaid += $budget;
            $totalTripAll += $budget;

            if (is_array($t->allowance_breakdown) && count($t->allowance_breakdown) > 0) {
                foreach ($t->allowance_breakdown as $item) {
                    $cat = $item['category'] ?? $item['item'] ?? 'Biaya Perjalanan';
                    $amt = floatval($item['amount'] ?? 0);
                    if (!isset($tripComponents[$cat])) {
                        $tripComponents[$cat] = [
                            'name' => $cat,
                            'type' => 'business_trip',
                            'count' => 0,
                            'paid_total' => 0,
                            'pending_total' => 0,
                            'total' => 0,
                            'items' => [],
                        ];
                    }
                    $tripComponents[$cat]['count']++;
                    $tripComponents[$cat]['total'] += $amt;
                    if ($isPaid) {
                        $tripComponents[$cat]['paid_total'] += $amt;
                    } else {
                        $tripComponents[$cat]['pending_total'] += $amt;
                    }
                    $tripComponents[$cat]['items'][] = [
                        'id' => $t->id,
                        'request_number' => $t->request_number,
                        'applicant' => $t->user?->name ?? 'Karyawan',
                        'amount' => $amt,
                        'description' => "Tujuan: {$t->destination} ({$cat})",
                        'status' => $t->status->value,
                        'status_label' => $t->status->label(),
                        'date' => $t->departure_date?->format('d M Y') ?? $t->created_at->format('d M Y'),
                    ];
                }
            } else {
                $cat = 'Biaya Terpadu';
                if (!isset($tripComponents[$cat])) {
                    $tripComponents[$cat] = [
                        'name' => $cat,
                        'type' => 'business_trip',
                        'count' => 0,
                        'paid_total' => 0,
                        'pending_total' => 0,
                        'total' => 0,
                        'items' => [],
                    ];
                }
                $tripComponents[$cat]['count']++;
                $tripComponents[$cat]['total'] += $budget;
                if ($isPaid) {
                    $tripComponents[$cat]['paid_total'] += $budget;
                } else {
                    $tripComponents[$cat]['pending_total'] += $budget;
                }
                $tripComponents[$cat]['items'][] = [
                    'id' => $t->id,
                    'request_number' => $t->request_number,
                    'applicant' => $t->user?->name ?? 'Karyawan',
                    'amount' => $budget,
                    'description' => "Tujuan: {$t->destination}",
                    'status' => $t->status->value,
                    'status_label' => $t->status->label(),
                    'date' => $t->departure_date?->format('d M Y') ?? $t->created_at->format('d M Y'),
                ];
            }
        }

        $businessTripData = array_values($tripComponents);
        usort($businessTripData, fn($a, $b) => $b['total'] <=> $a['total']);

        // 4. Vendor & Monthly Bills
        $vendorPayments = \App\Models\VendorPayment::with('vendor')->latest()->get();
        $vendorData = [];
        $totalVendorPaid = 0;

        foreach ($vendorPayments as $vp) {
            $vName = $vp->vendor?->name ?? 'Vendor Layanan';
            if (!isset($vendorData[$vName])) {
                $vendorData[$vName] = [
                    'name' => $vName,
                    'type' => 'vendor',
                    'count' => 0,
                    'paid_total' => 0,
                    'pending_total' => 0,
                    'total' => 0,
                    'items' => [],
                ];
            }
            $amt = (float)$vp->amount;
            $vendorData[$vName]['count']++;
            $vendorData[$vName]['total'] += $amt;
            $vendorData[$vName]['paid_total'] += $amt;
            $totalVendorPaid += $amt;
            $vendorData[$vName]['items'][] = [
                'id' => $vp->id,
                'request_number' => $vp->payment_reference ?? 'VP-' . $vp->id,
                'applicant' => $vName,
                'amount' => $amt,
                'description' => $vp->notes ?? 'Pembayaran Biaya Vendor',
                'status' => 'paid',
                'status_label' => 'Selesai',
                'date' => $vp->payment_date?->format('d M Y') ?? $vp->created_at->format('d M Y'),
            ];
        }

        $cashBills = CashTransaction::where('type', 'out')->where('category', '!=', 'mutasi')->whereNull('source_type')->latest()->get();
        foreach ($cashBills as $cb) {
            $bName = ucwords(str_replace('_', ' ', $cb->category ?? 'Biaya Kantor'));
            if (!isset($vendorData[$bName])) {
                $vendorData[$bName] = [
                    'name' => $bName,
                    'type' => 'vendor',
                    'count' => 0,
                    'paid_total' => 0,
                    'pending_total' => 0,
                    'total' => 0,
                    'items' => [],
                ];
            }
            $amt = (float)$cb->amount;
            $vendorData[$bName]['count']++;
            $vendorData[$bName]['total'] += $amt;
            $vendorData[$bName]['paid_total'] += $amt;
            $totalVendorPaid += $amt;
            $vendorData[$bName]['items'][] = [
                'id' => $cb->id,
                'request_number' => 'CSH-' . $cb->id,
                'applicant' => 'Keuangan Kas',
                'amount' => $amt,
                'description' => $cb->description ?? 'Pengeluaran Kas Operasional',
                'status' => 'paid',
                'status_label' => 'Selesai',
                'date' => $cb->transaction_date ? Carbon::parse($cb->transaction_date)->format('d M Y') : $cb->created_at->format('d M Y'),
            ];
        }

        $vendorBillData = array_values($vendorData);
        usort($vendorBillData, fn($a, $b) => $b['total'] <=> $a['total']);

        // 5. Top Subcategories across all streams
        $allSubcategories = array_merge(
            array_map(fn($i) => array_merge($i, ['group' => 'Reimbursement']), $reimbursementData),
            array_map(fn($i) => array_merge($i, ['group' => 'Operasional']), $operationalData),
            array_map(fn($i) => array_merge($i, ['group' => 'Perjalanan Dinas']), $businessTripData),
            array_map(fn($i) => array_merge($i, ['group' => 'Vendor & Tagihan']), $vendorBillData)
        );
        usort($allSubcategories, fn($a, $b) => $b['total'] <=> $a['total']);

        $grandTotalAll = array_sum(array_column($allSubcategories, 'total'));
        $grandTotalPaid = array_sum(array_column($allSubcategories, 'paid_total'));
        $grandTotalPending = array_sum(array_column($allSubcategories, 'pending_total'));

        foreach ($allSubcategories as &$sub) {
            $sub['percentage'] = $grandTotalAll > 0 ? round(($sub['total'] / $grandTotalAll) * 100, 1) : 0;
        }
        foreach ($reimbursementData as &$sub) {
            $sub['percentage'] = $totalReimbAll > 0 ? round(($sub['total'] / $totalReimbAll) * 100, 1) : 0;
        }
        foreach ($operationalData as &$sub) {
            $sub['percentage'] = $totalOpsAll > 0 ? round(($sub['total'] / $totalOpsAll) * 100, 1) : 0;
        }
        foreach ($businessTripData as &$sub) {
            $sub['percentage'] = $totalTripAll > 0 ? round(($sub['total'] / $totalTripAll) * 100, 1) : 0;
        }
        foreach ($vendorBillData as &$sub) {
            $sub['percentage'] = $totalVendorPaid > 0 ? round(($sub['total'] / $totalVendorPaid) * 100, 1) : 0;
        }

        return [
            'totals' => [
                'all' => $grandTotalAll,
                'paid' => $grandTotalPaid,
                'pending' => $grandTotalPending,
            ],
            'ai_summary' => [
                'total_amount' => $aiTotalAmount,
                'total_count' => $aiTotalCount,
                'percentage_of_reimbursement' => $totalReimbAll > 0 ? round(($aiTotalAmount / $totalReimbAll) * 100, 1) : 0,
            ],
            'all_subcategories' => $allSubcategories,
            'reimbursement' => $reimbursementData,
            'operational' => $operationalData,
            'business_trip' => $businessTripData,
            'vendor_and_bills' => $vendorBillData,
        ];
    }

    private function getLeavesByMonth()
    {
        $data = [];
        $now = now();
        for ($i = 5; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $count = \App\Models\LeaveRequest::whereIn('status', ['approved', 'completed'])
                ->whereYear('start_date', $date->year)
                ->whereMonth('start_date', $date->month)
                ->count();
            $data[] = [
                'month' => $date->format('M Y'),
                'year' => $date->year,
                'month_num' => $date->month,
                'count' => $count
            ];
        }
        return $data;
    }

    private function getChartData($period)
    {
        $labels = [];
        $revenueData = [];
        $expenseData = [];
        $revenueCategories = [];
        $expenseCategories = [];

        $now = now();
        
        $getRevCat = function($query, $cashInQuery) {
            $defaults = [
                'Invoicing Umum' => 0,
                'Renewal Webpraktis' => 0,
                'Transaksi Lainnya' => 0
            ];
            $data = (clone $query)->join('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id')
                ->selectRaw('invoices.source_type as category, sum(invoice_payments.amount) as total')
                ->groupBy('invoices.source_type')
                ->get();
            foreach ($data as $r) {
                $cat = $r->category == 'renewal' ? 'Renewal Webpraktis' : 'Invoicing Umum';
                $defaults[$cat] = (float)$r->total;
            }
            if ($cashInQuery) {
                 $defaults['Transaksi Lainnya'] = (float)(clone $cashInQuery)->where('category', '!=', 'mutasi')->whereNull('source_type')->sum('amount');
            }
            $res = [];
            foreach ($defaults as $k => $v) {
                $res[] = ['category' => $k, 'total' => $v];
            }
            return $res;
        };

        $getExpCat = function($queryVendor, $queryReimb, $queryOp, $queryBt, $queryCash) {
            return [
                ['category' => 'Vendor Renewal', 'total' => (float)(clone $queryVendor)->sum('amount')],
                ['category' => 'Reimbursement', 'total' => (float)(clone $queryReimb)->sum('amount')],
                ['category' => 'Operasional', 'total' => (float)(clone $queryOp)->sum('estimated_cost')],
                ['category' => 'Perjalanan Dinas', 'total' => (float)(clone $queryBt)->sum('disbursed_budget')],
                ['category' => 'Lainnya', 'total' => (float)(clone $queryCash)->where('category', '!=', 'mutasi')->whereNull('source_type')->sum('amount')],
            ];
        };
        
        $bounds = [];
        if ($period == 'daily') {
            for ($i = 6; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i);
                $labels[] = $date->format('d M');
                
                $rQuery = InvoicePayment::whereDate('payment_date', $date);
                $rCashInQuery = CashTransaction::where('type', 'in')->whereDate('transaction_date', $date);
                $eQueryVendor = VendorPayment::whereDate('payment_date', $date);
                $eQueryReimb = ReimbursementRequest::whereIn('status', ['paid', 'completed'])->whereDate('updated_at', $date);
                $eQueryOp = OperationalRequest::whereIn('status', ['paid', 'completed'])->whereDate('updated_at', $date);
                $eQueryBt = \App\Models\BusinessTripRequest::whereIn('status', ['paid', 'completed'])->whereDate('updated_at', $date);
                $eQueryCash = CashTransaction::where('type', 'out')->whereDate('transaction_date', $date);
                
                $revenueData[] = (clone $rQuery)->sum('amount') + (clone $rCashInQuery)->where('category', '!=', 'mutasi')->whereNull('source_type')->sum('amount');
                $expenseData[] = (clone $eQueryVendor)->sum('amount') + (clone $eQueryReimb)->sum('amount') + (clone $eQueryOp)->sum('estimated_cost') + (clone $eQueryBt)->sum('disbursed_budget') + (clone $eQueryCash)->where('category', '!=', 'mutasi')->whereNull('source_type')->sum('amount');
                
                $revenueCategories[] = $getRevCat($rQuery, $rCashInQuery);
                $expenseCategories[] = $getExpCat($eQueryVendor, $eQueryReimb, $eQueryOp, $eQueryBt, $eQueryCash);
                $bounds[] = ['start' => $date->format('Y-m-d'), 'end' => $date->format('Y-m-d')];
            }
        } elseif ($period == 'weekly') {
            for ($i = 3; $i >= 0; $i--) {
                $start = $now->copy()->subWeeks($i)->startOfWeek();
                $end = $now->copy()->subWeeks($i)->endOfWeek();
                $labels[] = $start->format('d M') . ' - ' . $end->format('d M');
                
                $rQuery = InvoicePayment::whereBetween('payment_date', [$start, $end]);
                $rCashInQuery = CashTransaction::where('type', 'in')->whereBetween('transaction_date', [$start, $end]);
                $eQueryVendor = VendorPayment::whereBetween('payment_date', [$start, $end]);
                $eQueryReimb = ReimbursementRequest::whereIn('status', ['paid', 'completed'])->whereBetween('updated_at', [$start, $end]);
                $eQueryOp = OperationalRequest::whereIn('status', ['paid', 'completed'])->whereBetween('updated_at', [$start, $end]);
                $eQueryBt = \App\Models\BusinessTripRequest::whereIn('status', ['paid', 'completed'])->whereBetween('updated_at', [$start, $end]);
                $eQueryCash = CashTransaction::where('type', 'out')->whereBetween('transaction_date', [$start, $end]);
                
                $revenueData[] = (clone $rQuery)->sum('amount') + (clone $rCashInQuery)->where('category', '!=', 'mutasi')->whereNull('source_type')->sum('amount');
                $expenseData[] = (clone $eQueryVendor)->sum('amount') + (clone $eQueryReimb)->sum('amount') + (clone $eQueryOp)->sum('estimated_cost') + (clone $eQueryBt)->sum('disbursed_budget') + (clone $eQueryCash)->where('category', '!=', 'mutasi')->whereNull('source_type')->sum('amount');
                
                $revenueCategories[] = $getRevCat($rQuery, $rCashInQuery);
                $expenseCategories[] = $getExpCat($eQueryVendor, $eQueryReimb, $eQueryOp, $eQueryBt, $eQueryCash);
                $bounds[] = ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')];
            }
        } else {
            // Monthly
            for ($i = 5; $i >= 0; $i--) {
                $date = $now->copy()->subMonths($i);
                $labels[] = $date->format('M Y');
                
                $rQuery = InvoicePayment::whereYear('payment_date', $date->year)->whereMonth('payment_date', $date->month);
                $rCashInQuery = CashTransaction::where('type', 'in')->whereYear('transaction_date', $date->year)->whereMonth('transaction_date', $date->month);
                $eQueryVendor = VendorPayment::whereYear('payment_date', $date->year)->whereMonth('payment_date', $date->month);
                $eQueryReimb = ReimbursementRequest::whereIn('status', ['paid', 'completed'])->whereYear('updated_at', $date->year)->whereMonth('updated_at', $date->month);
                $eQueryOp = OperationalRequest::whereIn('status', ['paid', 'completed'])->whereYear('updated_at', $date->year)->whereMonth('updated_at', $date->month);
                $eQueryBt = \App\Models\BusinessTripRequest::whereIn('status', ['paid', 'completed'])->whereYear('updated_at', $date->year)->whereMonth('updated_at', $date->month);
                $eQueryCash = CashTransaction::where('type', 'out')->whereYear('transaction_date', $date->year)->whereMonth('transaction_date', $date->month);
                
                $revenueData[] = (clone $rQuery)->sum('amount') + (clone $rCashInQuery)->where('category', '!=', 'mutasi')->whereNull('source_type')->sum('amount');
                $expenseData[] = (clone $eQueryVendor)->sum('amount') + (clone $eQueryReimb)->sum('amount') + (clone $eQueryOp)->sum('estimated_cost') + (clone $eQueryBt)->sum('disbursed_budget') + (clone $eQueryCash)->where('category', '!=', 'mutasi')->whereNull('source_type')->sum('amount');
                
                $revenueCategories[] = $getRevCat($rQuery, $rCashInQuery);
                $expenseCategories[] = $getExpCat($eQueryVendor, $eQueryReimb, $eQueryOp, $eQueryBt, $eQueryCash);
                $bounds[] = ['start' => $date->copy()->startOfMonth()->format('Y-m-d'), 'end' => $date->copy()->endOfMonth()->format('Y-m-d')];
            }
        }

        return [
            'labels' => $labels,
            'revenue' => $revenueData,
            'expense' => $expenseData,
            'revenue_categories' => $revenueCategories,
            'expense_categories' => $expenseCategories,
            'bounds' => $bounds,
        ];
    }

    public function breakdown(Request $request)
    {
        $category = $request->query('category');
        $start = $request->query('start');
        $end = $request->query('end');

        $results = [];

        if ($category === 'Reimbursement') {
            $query = \App\Models\ReimbursementRequest::whereIn('status', ['paid', 'completed'])
                ->join('expense_types', 'reimbursement_requests.expense_type_id', '=', 'expense_types.id')
                ->selectRaw('expense_types.name as label, sum(reimbursement_requests.amount) as total');
            if ($start && $end) {
                $query->whereBetween('reimbursement_requests.updated_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
            }
            $data = $query->groupBy('expense_types.name')->get();
            foreach ($data as $d) { $results[] = ['label' => $d->label, 'total' => (float)$d->total]; }

        } elseif ($category === 'Operasional') {
            $query = \App\Models\OperationalRequest::whereIn('status', ['paid', 'completed'])
                ->join('activity_types', 'operational_requests.activity_type_id', '=', 'activity_types.id')
                ->selectRaw('activity_types.name as label, sum(operational_requests.estimated_cost) as total');
            if ($start && $end) {
                $query->whereBetween('operational_requests.updated_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
            }
            $data = $query->groupBy('activity_types.name')->get();
            foreach ($data as $d) { $results[] = ['label' => $d->label, 'total' => (float)$d->total]; }

        } elseif ($category === 'Perjalanan Dinas') {
            $query = \App\Models\BusinessTripRequest::whereIn('status', ['paid', 'completed']);
            if ($start && $end) {
                $query->whereBetween('updated_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
            }
            $trips = $query->get(['allowance_breakdown']);
            $breakdown = [];
            foreach ($trips as $t) {
                if (is_array($t->allowance_breakdown)) {
                    foreach ($t->allowance_breakdown as $item) {
                        $cat = $item['category'] ?? 'Lainnya';
                        $amt = floatval($item['amount'] ?? 0);
                        if (!isset($breakdown[$cat])) $breakdown[$cat] = 0;
                        $breakdown[$cat] += $amt;
                    }
                }
            }
            foreach ($breakdown as $lbl => $amt) { $results[] = ['label' => $lbl, 'total' => $amt]; }

        } elseif ($category === 'Invoicing Umum') {
            $query = \App\Models\InvoiceItem::join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->join('invoice_payments', 'invoices.id', '=', 'invoice_payments.invoice_id')
                ->where('invoices.source_type', 'general')
                ->selectRaw('invoice_items.description as label, sum(invoice_items.subtotal) as total');
            if ($start && $end) {
                $query->whereBetween('invoice_payments.payment_date', [$start, $end]);
            }
            $data = $query->groupBy('invoice_items.description')->get();
            foreach ($data as $d) { $results[] = ['label' => $d->label, 'total' => (float)$d->total]; }

        } elseif ($category === 'Renewal Webpraktis') {
            $query = \App\Models\Invoice::where('invoices.source_type', 'renewal')
                ->join('invoice_payments', 'invoices.id', '=', 'invoice_payments.invoice_id')
                ->join('renewal_requests', 'invoices.source_id', '=', 'renewal_requests.id')
                ->join('domains', 'renewal_requests.domain_id', '=', 'domains.id')
                ->join('vendors', 'domains.vendor_id', '=', 'vendors.id')
                ->selectRaw('vendors.name as label, sum(invoice_payments.amount) as total');
            if ($start && $end) {
                $query->whereBetween('invoice_payments.payment_date', [$start, $end]);
            }
            $data = $query->groupBy('vendors.name')->get();
            foreach ($data as $d) { $results[] = ['label' => $d->label, 'total' => (float)$d->total]; }

        } elseif ($category === 'Transaksi Lainnya') {
            $query = \App\Models\CashTransaction::where('type', 'in')->where('category', '!=', 'mutasi');
            if ($start && $end) {
                $query->whereBetween('transaction_date', [$start, $end]);
            }
            $data = $query->selectRaw('category as label, sum(amount) as total')->groupBy('category')->get();
            foreach ($data as $d) {
                $label = ucwords(str_replace('_', ' ', $d->label));
                $results[] = ['label' => $label, 'total' => (float)$d->total];
            }
        } elseif ($category === 'Aset') {
            $catName = $request->query('asset_category');
            $query = \App\Models\Asset::with(['coaAsset', 'depreciations' => function($q) {
                $q->whereYear('depreciation_date', now()->year)->whereMonth('depreciation_date', now()->month);
            }]);
            if ($catName) {
                $query->whereHas('coaAsset', function($q) use ($catName) {
                    $q->where('name', $catName);
                });
            }
            $assets = $query->get();
            foreach ($assets as $a) {
                $depThisMonth = $a->depreciations->sum('depreciation_amount');
                $results[] = [
                    'asset_name' => $a->name,
                    'purchase_price' => (float)$a->purchase_price,
                    'depreciation_this_month' => (float)$depThisMonth,
                    'book_value' => (float)$a->book_value,
                    'is_table' => true
                ];
            }
            return response()->json($results);

        } elseif ($category === 'Cuti') {
            $year = $request->query('year');
            $month = $request->query('month');
            
            $query = \App\Models\LeaveRequest::with(['user.division', 'leaveType'])->whereIn('status', ['approved', 'completed']);
            if ($year && $month) {
                $query->whereYear('start_date', $year)->whereMonth('start_date', $month);
            }
            
            $leaves = $query->get();
            foreach ($leaves as $l) {
                $results[] = [
                    'applicant_name' => $l->user->name,
                    'division' => $l->user->division->name ?? '-',
                    'leave_type' => $l->leaveType->name ?? '-',
                    'start_date' => $l->start_date->format('d M Y'),
                    'total_days' => $l->total_days,
                    'is_leave_table' => true
                ];
            }
            return response()->json($results);

        } elseif ($category === 'Pembayaran Bulanan') {
            $query = \App\Models\MonthlyBillPayment::whereIn('monthly_bill_payments.status', ['paid', 'completed'])
                ->join('monthly_bill_types', 'monthly_bill_payments.bill_type_id', '=', 'monthly_bill_types.id')
                ->selectRaw('monthly_bill_types.name as label, sum(monthly_bill_payments.bill_amount) as total');
            if ($start && $end) {
                $query->whereBetween('monthly_bill_payments.updated_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
            }
            $data = $query->groupBy('monthly_bill_types.name')->get();
            foreach ($data as $d) { $results[] = ['label' => $d->label, 'total' => (float)$d->total]; }
        } else {
            // Default for other types
            $query = \App\Models\CashTransaction::where('type', 'out');
            if ($start && $end) {
                $query->whereBetween('transaction_date', [$start, $end]);
            }
            // we don't have sub-categories for these yet, just return the total
            $results[] = ['label' => 'Total ' . $category, 'total' => (float)$query->sum('amount')];
        }

        // Only sort if it's not a table format
        if (count($results) > 0 && !isset($results[0]['is_table']) && !isset($results[0]['is_leave_table'])) {
            usort($results, fn($a, $b) => $b['total'] <=> $a['total']);
        }
        return response()->json($results);
    }
}

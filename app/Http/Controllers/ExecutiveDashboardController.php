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
        $revenueTotal = InvoicePayment::sum('amount') + CashTransaction::where('type', 'in')->where('category', '!=', 'mutasi')->sum('amount');
        $piutangTotal = \App\Models\Invoice::whereNotIn('status', ['paid', 'cancelled'])->get()->sum(function($inv) {
            return $inv->total_amount - $inv->paid_amount;
        });
        
        $expenseTotal = VendorPayment::sum('amount') 
            + ReimbursementRequest::whereIn('status', ['paid', 'completed'])->sum('amount')
            + OperationalRequest::whereIn('status', ['paid', 'completed'])->sum('estimated_cost')
            + \App\Models\BusinessTripRequest::whereIn('status', ['paid', 'completed'])->sum('disbursed_budget');
            
        $cashExpense = CashTransaction::where('type', 'out')->where('category', '!=', 'mutasi')->sum('amount');
        if ($cashExpense > $expenseTotal) {
            $expenseTotal = $cashExpense;
        }

        // Category Breakdown
        $defaultExpenseCats = [
            'Vendor Renewal' => 0,
            'Reimbursement' => 0,
            'Operasional' => 0,
            'Perjalanan Dinas' => 0,
            'Pembayaran Bulanan' => 0
        ];
        
        $cashTransactions = CashTransaction::where('type', 'out')->where('category', '!=', 'mutasi')->selectRaw('category, sum(amount) as total')->groupBy('category')->get();
        if ($cashTransactions->count() > 0) {
            foreach ($cashTransactions as $ct) {
                $cat = strtolower($ct->category);
                $mappedName = 'Operasional';
                if ($cat === 'perjalanan_dinas' || $cat === 'perjalanan dinas') $mappedName = 'Perjalanan Dinas';
                elseif ($cat === 'reimburse' || $cat === 'reimbursement') $mappedName = 'Reimbursement';
                elseif ($cat === 'pembayaran_bulanan' || $cat === 'pembayaran bulanan') $mappedName = 'Pembayaran Bulanan';
                elseif (in_array($cat, ['lainnya', 'vendor_renewal', 'vendor renewal'])) $mappedName = 'Vendor Renewal';
                
                $defaultExpenseCats[$mappedName] += (float)$ct->total;
            }
        } else {
            $defaultExpenseCats['Vendor Renewal'] = (float)\App\Models\VendorPayment::sum('amount');
            $defaultExpenseCats['Reimbursement'] = (float)\App\Models\ReimbursementRequest::whereIn('status', ['paid', 'completed'])->sum('amount');
            $defaultExpenseCats['Operasional'] = (float)\App\Models\OperationalRequest::whereIn('status', ['paid', 'completed'])->sum('estimated_cost');
            $defaultExpenseCats['Perjalanan Dinas'] = (float)\App\Models\BusinessTripRequest::whereIn('status', ['paid', 'completed'])->sum('disbursed_budget');
        }

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

        $cashInTrans = CashTransaction::where('type', 'in')->where('category', '!=', 'mutasi')->sum('amount');
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
        ]);
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
                 $defaults['Transaksi Lainnya'] = (float)(clone $cashInQuery)->where('category', '!=', 'mutasi')->sum('amount');
            }
            $res = [];
            foreach ($defaults as $k => $v) {
                $res[] = ['category' => $k, 'total' => $v];
            }
            return $res;
        };

        $getExpCat = function($query) {
            $defaults = [
                'Vendor Renewal' => 0,
                'Reimbursement' => 0,
                'Operasional' => 0,
                'Perjalanan Dinas' => 0,
                'Pembayaran Bulanan' => 0
            ];
            $data = (clone $query)->where('category', '!=', 'mutasi')->selectRaw('category, sum(amount) as total')
                ->groupBy('category')
                ->get();
            foreach ($data as $ct) {
                $cat = strtolower($ct->category);
                $mappedName = 'Operasional';
                if ($cat === 'perjalanan_dinas' || $cat === 'perjalanan dinas') $mappedName = 'Perjalanan Dinas';
                elseif ($cat === 'reimburse' || $cat === 'reimbursement') $mappedName = 'Reimbursement';
                elseif ($cat === 'pembayaran_bulanan' || $cat === 'pembayaran bulanan') $mappedName = 'Pembayaran Bulanan';
                elseif (in_array($cat, ['lainnya', 'vendor_renewal', 'vendor renewal'])) $mappedName = 'Vendor Renewal';
                
                $defaults[$mappedName] += (float)$ct->total;
            }
            $res = [];
            foreach ($defaults as $k => $v) {
                $res[] = ['category' => $k, 'total' => $v];
            }
            return $res;
        };
        
        $bounds = [];
        if ($period == 'daily') {
            for ($i = 6; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i);
                $labels[] = $date->format('d M');
                
                $rQuery = InvoicePayment::whereDate('payment_date', $date);
                $rCashInQuery = CashTransaction::where('type', 'in')->whereDate('transaction_date', $date);
                $eQuery = CashTransaction::where('type', 'out')->where('category', '!=', 'mutasi')->whereDate('transaction_date', $date);
                
                $revenueData[] = (clone $rQuery)->sum('amount') + (clone $rCashInQuery)->where('category', '!=', 'mutasi')->sum('amount');
                $expenseData[] = (clone $eQuery)->sum('amount');
                
                $revenueCategories[] = $getRevCat($rQuery, $rCashInQuery);
                $expenseCategories[] = $getExpCat($eQuery);
                $bounds[] = ['start' => $date->format('Y-m-d'), 'end' => $date->format('Y-m-d')];
            }
        } elseif ($period == 'weekly') {
            for ($i = 3; $i >= 0; $i--) {
                $start = $now->copy()->subWeeks($i)->startOfWeek();
                $end = $now->copy()->subWeeks($i)->endOfWeek();
                $labels[] = $start->format('d M') . ' - ' . $end->format('d M');
                
                $rQuery = InvoicePayment::whereBetween('payment_date', [$start, $end]);
                $rCashInQuery = CashTransaction::where('type', 'in')->whereBetween('transaction_date', [$start, $end]);
                $eQuery = CashTransaction::where('type', 'out')->where('category', '!=', 'mutasi')->whereBetween('transaction_date', [$start, $end]);
                
                $revenueData[] = (clone $rQuery)->sum('amount') + (clone $rCashInQuery)->where('category', '!=', 'mutasi')->sum('amount');
                $expenseData[] = (clone $eQuery)->sum('amount');
                
                $revenueCategories[] = $getRevCat($rQuery, $rCashInQuery);
                $expenseCategories[] = $getExpCat($eQuery);
                $bounds[] = ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')];
            }
        } else {
            // Monthly
            for ($i = 5; $i >= 0; $i--) {
                $date = $now->copy()->subMonths($i);
                $labels[] = $date->format('M Y');
                
                $rQuery = InvoicePayment::whereYear('payment_date', $date->year)->whereMonth('payment_date', $date->month);
                $rCashInQuery = CashTransaction::where('type', 'in')->whereYear('transaction_date', $date->year)->whereMonth('transaction_date', $date->month);
                $eQuery = CashTransaction::where('type', 'out')->where('category', '!=', 'mutasi')->whereYear('transaction_date', $date->year)->whereMonth('transaction_date', $date->month);
                
                $revenueData[] = (clone $rQuery)->sum('amount') + (clone $rCashInQuery)->where('category', '!=', 'mutasi')->sum('amount');
                $expenseData[] = (clone $eQuery)->sum('amount');
                
                $revenueCategories[] = $getRevCat($rQuery, $rCashInQuery);
                $expenseCategories[] = $getExpCat($eQuery);
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
        } else {
            // Default for other types like Pembayaran Bulanan / Vendor Renewal manual
            $query = \App\Models\CashTransaction::where('type', 'out');
            if ($start && $end) {
                $query->whereBetween('transaction_date', [$start, $end]);
            }
            // we don't have sub-categories for these yet, just return the total
            $results[] = ['label' => 'Total ' . $category, 'total' => (float)$query->sum('amount')];
        }

        usort($results, fn($a, $b) => $b['total'] <=> $a['total']);
        return response()->json($results);
    }
}

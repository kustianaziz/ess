<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coa;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Services\FinancialReportService;
use Inertia\Inertia;
use Carbon\Carbon;

class FinancialReportController extends Controller
{
    protected $reportService;

    public function __construct(FinancialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function ledger(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $coaId = $request->input('coa_id');
        $level = $request->input('level', 5);
        $showZero = filter_var($request->input('show_zero', true), FILTER_VALIDATE_BOOLEAN);
        $showCode = filter_var($request->input('show_code', false), FILTER_VALIDATE_BOOLEAN);

        $reportData = $this->reportService->getCoaTreeWithBalances($startDate, $endDate, null, $level, $showZero);
        
        $transactions = [];
        $selectedCoa = null;
        $beginningBalance = 0;
        $endingBalance = 0;

        if ($coaId) {
            $selectedCoa = Coa::find($coaId);
            if ($selectedCoa) {
                // Get Beginning Balance (before startDate)
                $pastItems = JournalItem::where('coa_id', $coaId)
                    ->whereHas('journalEntry', function($q) use ($startDate) {
                        $q->where('date', '<', $startDate);
                    })->get();
                
                $beginningBalance = $pastItems->sum('debit') - $pastItems->sum('credit');
                if (in_array(strtolower($selectedCoa->normal_balance), ['kredit', 'credit'])) {
                    $beginningBalance = $pastItems->sum('credit') - $pastItems->sum('debit');
                }

                $items = JournalItem::with('journalEntry')
                    ->where('coa_id', $coaId)
                    ->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
                        $q->whereBetween('date', [$startDate, $endDate]);
                    })
                    ->get()
                    ->sortBy(function($item) {
                        return $item->journalEntry->date;
                    });
                
                $currentBalance = $beginningBalance;
                foreach($items as $item) {
                    if (in_array(strtolower($selectedCoa->normal_balance), ['kredit', 'credit'])) {
                        $currentBalance += $item->credit - $item->debit;
                    } else {
                        $currentBalance += $item->debit - $item->credit;
                    }
                    $transactions[] = [
                        'id' => $item->id,
                        'date' => $item->journalEntry->date->format('Y-m-d'),
                        'description' => $item->description ?? $item->journalEntry->description,
                        'reference' => $item->journalEntry->journal_number,
                        'debit' => $item->debit,
                        'credit' => $item->credit,
                        'balance' => $currentBalance,
                    ];
                }
                $endingBalance = $currentBalance;
            }
        }

        $data = [
            'coas' => $reportData['flat'],
            'maxLevel' => $reportData['maxLevel'] ?? 5,
            'transactions' => array_values($transactions),
            'selectedCoa' => $selectedCoa,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'coa_id' => $coaId,
                'level' => $level,
                'show_zero' => $showZero,
                'show_code' => $showCode,
            ],
            'beginningBalance' => $beginningBalance,
            'endingBalance' => $endingBalance,
        ];

        if ($request->input('export') === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.ledger', $data);
            return $pdf->download('BukuBesar_'.$startDate.'_'.$endDate.'.pdf');
        } else if ($request->input('export') === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\FinancialReportExport('exports.ledger', $data), 'BukuBesar_'.$startDate.'_'.$endDate.'.xlsx');
        }

        return Inertia::render('Accounting/Reports/Ledger', $data);
    }

    public function incomeStatement(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $level = $request->input('level', 5);
        $showZero = filter_var($request->input('show_zero', true), FILTER_VALIDATE_BOOLEAN);
        $showCode = filter_var($request->input('show_code', false), FILTER_VALIDATE_BOOLEAN);

        $reportData = $this->reportService->getCoaTreeWithBalances($startDate, $endDate, null, $level, $showZero);
        $coas = collect($reportData['flat']);

        $filterByCategory = function($prefix) use ($coas) {
            $items = $coas->filter(function($c) use ($prefix) {
                return str_starts_with($c['code'], $prefix);
            })->values();
            $total = collect($items)->sum('balance');
            return ['items' => $items, 'total' => $total];
        };

        $revenues = $filterByCategory('4');
        $expenses = $filterByCategory('5');
        $otherRevenues = $filterByCategory('6');
        $otherExpenses = $filterByCategory('7');
        $taxes = $filterByCategory('8');

        $grossProfit = $revenues['total'] - $expenses['total'];
        $operatingProfit = $grossProfit + $otherRevenues['total'] - $otherExpenses['total'];
        $netProfit = $operatingProfit - $taxes['total'];

        $data = [
            'filters' => [
                'start_date' => $startDate, 'end_date' => $endDate,
                'level' => $level, 'show_zero' => $showZero, 'show_code' => $showCode
            ],
            'maxLevel' => $reportData['maxLevel'] ?? 5,
            'revenues' => $revenues,
            'expenses' => $expenses,
            'otherRevenues' => $otherRevenues,
            'otherExpenses' => $otherExpenses,
            'taxes' => $taxes,
            'grossProfit' => $grossProfit,
            'operatingProfit' => $operatingProfit,
            'netProfit' => $netProfit,
        ];

        if ($request->input('export') === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.income_statement', $data);
            return $pdf->download('LabaRugi_'.$startDate.'_'.$endDate.'.pdf');
        } else if ($request->input('export') === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\FinancialReportExport('exports.income_statement', $data), 'LabaRugi_'.$startDate.'_'.$endDate.'.xlsx');
        }

        return Inertia::render('Accounting/Reports/IncomeStatement', $data);
    }

    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->input('as_of_date', Carbon::now()->format('Y-m-d'));
        $level = $request->input('level', 5);
        $showZero = filter_var($request->input('show_zero', true), FILTER_VALIDATE_BOOLEAN);
        $showCode = filter_var($request->input('show_code', false), FILTER_VALIDATE_BOOLEAN);

        $reportData = $this->reportService->getCoaTreeWithBalances(null, null, $asOfDate, $level, $showZero);
        $coas = collect($reportData['flat']);

        $filterByCategory = function($prefix) use ($coas) {
            $items = $coas->filter(function($c) use ($prefix) {
                return str_starts_with($c['code'], $prefix);
            })->values()->toArray();
            $total = collect($items)->sum('balance');
            return ['items' => $items, 'total' => $total];
        };

        $assets = $filterByCategory('1');
        $liabilities = $filterByCategory('2');
        $equities = $filterByCategory('3');

        // Retained Earnings (Net Profit from beginning of time until asOfDate)
        $revenueCoas = Coa::where('code', 'like', '4%')->orWhere('code', 'like', '6%')->get()->pluck('id');
        $expenseCoas = Coa::where('code', 'like', '5%')->orWhere('code', 'like', '7%')->orWhere('code', 'like', '8%')->get()->pluck('id');

        $totalRevenue = JournalItem::whereIn('coa_id', $revenueCoas)
            ->whereHas('journalEntry', function($q) use ($asOfDate) {
                $q->where('date', '<=', $asOfDate);
            })->get()->reduce(function($carry, $item) {
                return $carry + ($item->credit - $item->debit);
            }, 0);

        $totalExpense = JournalItem::whereIn('coa_id', $expenseCoas)
            ->whereHas('journalEntry', function($q) use ($asOfDate) {
                $q->where('date', '<=', $asOfDate);
            })->get()->reduce(function($carry, $item) {
                return $carry + ($item->debit - $item->credit);
            }, 0);

        $retainedEarnings = $totalRevenue - $totalExpense;

        if ($showZero || $retainedEarnings != 0) {
            $equities['items'][] = [
                'code' => '3-RE',
                'name' => 'Laba Ditahan (Retained Earnings)',
                'balance' => $retainedEarnings,
                'level' => 2,
                'is_header' => false
            ];
            $equities['total'] += $retainedEarnings;
        }

        $data = [
            'filters' => [
                'as_of_date' => $asOfDate,
                'level' => $level, 'show_zero' => $showZero, 'show_code' => $showCode
            ],
            'maxLevel' => $reportData['maxLevel'] ?? 5,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equities' => $equities,
        ];

        if ($request->input('export') === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.balance_sheet', $data);
            return $pdf->download('Neraca_'.$asOfDate.'.pdf');
        } else if ($request->input('export') === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\FinancialReportExport('exports.balance_sheet', $data), 'Neraca_'.$asOfDate.'.xlsx');
        }

        return Inertia::render('Accounting/Reports/BalanceSheet', $data);
    }
    
    public function cashFlow(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $level = $request->input('level', 5);
        $showZero = filter_var($request->input('show_zero', true), FILTER_VALIDATE_BOOLEAN);
        $showCode = filter_var($request->input('show_code', false), FILTER_VALIDATE_BOOLEAN);

        $data = [
            'filters' => [
                'start_date' => $startDate, 'end_date' => $endDate,
                'level' => $level, 'show_zero' => $showZero, 'show_code' => $showCode
            ],
            'maxLevel' => 5,
            'operatingActivities' => [],
            'investingActivities' => [],
            'financingActivities' => [],
            'operatingTotal' => 0,
            'investingTotal' => 0,
            'financingTotal' => 0,
            'netIncrease' => 0,
            'beginningCash' => 0,
            'endingCash' => 0,
        ];

        if ($request->input('export') === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.cash_flow', $data);
            return $pdf->download('ArusKas_'.$startDate.'_'.$endDate.'.pdf');
        } else if ($request->input('export') === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\FinancialReportExport('exports.cash_flow', $data), 'ArusKas_'.$startDate.'_'.$endDate.'.xlsx');
        }

        return Inertia::render('Accounting/Reports/CashFlow', $data);
    }

    public function calk(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $level = $request->input('level', 5);
        $showZero = filter_var($request->input('show_zero', true), FILTER_VALIDATE_BOOLEAN);
        $showCode = filter_var($request->input('show_code', false), FILTER_VALIDATE_BOOLEAN);

        $reportService = new \App\Services\FinancialReportService();
        $reportData = $reportService->getCoaTreeWithBalances($startDate, $endDate, $level, $showZero);

        $coas = collect($reportData['flat'])->map(function($coa) use ($startDate, $endDate) {
            // Fetch transactions for detail accounts that have balance
            if (!$coa->is_header && ($coa->balance != 0 || $coa->raw_debit != 0 || $coa->raw_credit != 0)) {
                $transactions = \App\Models\JournalItem::where('coa_id', $coa->id)
                    ->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
                        $q->whereBetween('date', [$startDate, $endDate]);
                    })
                    ->with('journalEntry')
                    ->get()
                    ->map(function($item) {
                        return [
                            'date' => $item->journalEntry->date,
                            'reference' => $item->journalEntry->reference,
                            'description' => $item->description ?: $item->journalEntry->description,
                            'debit' => $item->debit,
                            'credit' => $item->credit,
                        ];
                    });
                $coa->transactions = $transactions;
            } else {
                $coa->transactions = [];
            }
            return $coa;
        });

        $data = [
            'coas' => $coas,
            'maxLevel' => $reportData['maxLevel'] ?? 5,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'level' => $level,
                'show_zero' => $showZero,
                'show_code' => $showCode,
            ],
        ];

        if ($request->input('export') === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.calk', $data);
            return $pdf->download('CALK_'.$startDate.'_'.$endDate.'.pdf');
        } else if ($request->input('export') === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\FinancialReportExport('exports.calk', $data), 'CALK_'.$startDate.'_'.$endDate.'.xlsx');
        }

        return Inertia::render('Accounting/Reports/Calk', $data);
    }
}

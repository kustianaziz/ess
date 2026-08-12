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
                        $q->where('date', '<', $startDate)
                          ->where('status', '!=', 'void');
                    })->get();
                
                $beginningBalance = $pastItems->sum('debit') - $pastItems->sum('credit');
                if (in_array(strtolower($selectedCoa->normal_balance), ['kredit', 'credit'])) {
                    $beginningBalance = $pastItems->sum('credit') - $pastItems->sum('debit');
                }

                $items = JournalItem::with('journalEntry')
                    ->where('coa_id', $coaId)
                    ->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
                        $q->whereBetween('date', [$startDate, $endDate])
                          ->where('status', '!=', 'void');
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

        $filterByType = function($type) use ($coas) {
            $items = $coas->filter(function($c) use ($type) {
                return $c['type'] === $type;
            })->values();
            $total = collect($items)->where('is_header', false)->sum('balance');
            return ['items' => $items, 'total' => $total];
        };

        $revenues = $filterByType('pendapatan');
        $expenses = $filterByType('beban');
        
        $otherRevenues = ['items' => [], 'total' => 0];
        $otherExpenses = ['items' => [], 'total' => 0];
        $taxes = ['items' => [], 'total' => 0];

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

        $filterByType = function($type) use ($coas) {
            $items = $coas->filter(function($c) use ($type) {
                return $c['type'] === $type;
            })->values()->toArray();
            $total = collect($items)->where('is_header', false)->sum('balance');
            return ['items' => $items, 'total' => $total];
        };

        $assets = $filterByType('aset');
        $liabilities = $filterByType('hutang');
        $equities = $filterByType('modal');

        // Retained Earnings & Current Year Earnings settings
        $retainedEarningsCoaId = \App\Models\Setting::get('retained_earnings_coa_id');
        $currentEarningsCoaId = \App\Models\Setting::get('current_earnings_coa_id');

        // Calculate Laba Tahun Berjalan (Current Year Earnings): from start of year to asOfDate
        $startOfYear = Carbon::parse($asOfDate)->startOfYear()->format('Y-m-d');
        
        $revenueCoas = Coa::where('type', 'pendapatan')->get()->pluck('id');
        $expenseCoas = Coa::where('type', 'beban')->get()->pluck('id');

        // Exclude void journals from calculations
        $currentYearRevenue = JournalItem::whereIn('coa_id', $revenueCoas)
            ->whereHas('journalEntry', function($q) use ($startOfYear, $asOfDate) {
                $q->whereBetween('date', [$startOfYear, $asOfDate])
                  ->where('status', '!=', 'void');
            })->get()->reduce(fn($carry, $item) => $carry + ($item->credit - $item->debit), 0);

        $currentYearExpense = JournalItem::whereIn('coa_id', $expenseCoas)
            ->whereHas('journalEntry', function($q) use ($startOfYear, $asOfDate) {
                $q->whereBetween('date', [$startOfYear, $asOfDate])
                  ->where('status', '!=', 'void');
            })->get()->reduce(fn($carry, $item) => $carry + ($item->debit - $item->credit), 0);

        $currentYearEarnings = $currentYearRevenue - $currentYearExpense;

        // Previous Years Net Profit (Retained Earnings)
        $prevRevenue = JournalItem::whereIn('coa_id', $revenueCoas)
            ->whereHas('journalEntry', function($q) use ($startOfYear) {
                $q->where('date', '<', $startOfYear)
                  ->where('status', '!=', 'void');
            })->get()->reduce(fn($carry, $item) => $carry + ($item->credit - $item->debit), 0);

        $prevExpense = JournalItem::whereIn('coa_id', $expenseCoas)
            ->whereHas('journalEntry', function($q) use ($startOfYear) {
                $q->where('date', '<', $startOfYear)
                  ->where('status', '!=', 'void');
            })->get()->reduce(fn($carry, $item) => $carry + ($item->debit - $item->credit), 0);

        $retainedEarnings = $prevRevenue - $prevExpense;

        // Map or override in equities list
        $retainedEarningsCoa = $retainedEarningsCoaId ? Coa::find($retainedEarningsCoaId) : null;
        $currentEarningsCoa = $currentEarningsCoaId ? Coa::find($currentEarningsCoaId) : null;

        $hasRetainedEarningsCoa = false;
        $hasCurrentEarningsCoa = false;

        foreach ($equities['items'] as &$item) {
            if ($retainedEarningsCoa && $item['id'] == $retainedEarningsCoa->id) {
                $item['balance'] += $retainedEarnings;
                $hasRetainedEarningsCoa = true;
            }
            if ($currentEarningsCoa && $item['id'] == $currentEarningsCoa->id) {
                $item['balance'] += $currentYearEarnings;
                $hasCurrentEarningsCoa = true;
            }
        }

        // If mapped but excluded due to zero-balance filter, force add it
        if ($retainedEarningsCoa && !$hasRetainedEarningsCoa) {
            $equities['items'][] = [
                'id' => $retainedEarningsCoa->id,
                'code' => $retainedEarningsCoa->code,
                'name' => $retainedEarningsCoa->name,
                'type' => 'modal',
                'balance' => $retainedEarnings,
                'level' => $retainedEarningsCoa->level,
                'is_header' => false
            ];
        }

        if ($currentEarningsCoa && !$hasCurrentEarningsCoa) {
            $equities['items'][] = [
                'id' => $currentEarningsCoa->id,
                'code' => $currentEarningsCoa->code,
                'name' => $currentEarningsCoa->name,
                'type' => 'modal',
                'balance' => $currentYearEarnings,
                'level' => $currentEarningsCoa->level,
                'is_header' => false
            ];
        }

        // Sort by code so they are in perfect tree order
        usort($equities['items'], fn($a, $b) => strcmp($a['code'], $b['code']));

        // Recalculate total equity and rollup header accounts so parent COAs show correct sums
        $recalculateHeaders = function(&$items) {
            foreach ($items as &$parent) {
                if ($parent['is_header']) {
                    $sum = 0;
                    foreach ($items as $child) {
                        if (!$child['is_header'] && str_starts_with($child['code'], $parent['code'] . '.')) {
                            $sum += $child['balance'];
                        }
                    }
                    $parent['balance'] = $sum;
                }
            }
        };

        $recalculateHeaders($equities['items']);

        $totalEquity = 0;
        foreach ($equities['items'] as $item) {
            if (!$item['is_header']) {
                $totalEquity += $item['balance'];
            }
        }
        $equities['total'] = $totalEquity;

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

        // Get beginning cash
        $beginningCashItems = \App\Models\JournalItem::whereHas('coa', function($q) {
            $q->where('code', 'like', '1.01%');
        })->whereHas('journalEntry', function($q) use ($startDate) {
            $q->where('date', '<', $startDate)->where('status', '!=', 'void');
        })->get();
        
        $beginningCash = $beginningCashItems->sum('debit') - $beginningCashItems->sum('credit');

        // Get all journal entries that involve cash
        $cashJournalIds = \App\Models\JournalItem::whereHas('coa', function($q) {
            $q->where('code', 'like', '1.01%');
        })->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate])->where('status', '!=', 'void');
        })->pluck('journal_entry_id')->unique();

        $operating = [];
        $investing = [];
        $financing = [];

        $items = \App\Models\JournalItem::with('coa')
            ->whereIn('journal_entry_id', $cashJournalIds)
            ->whereHas('coa', function($q) {
                $q->where('code', 'not like', '1.01%'); // non-cash items
            })
            ->get();

        foreach($items as $item) {
            $coa = $item->coa;
            $amount = $item->credit - $item->debit; // Cash inflow from this item
            
            if ($amount == 0) continue;

            $category = 'Operating';
            if ($coa->type == 'pendapatan' || $coa->type == 'beban') {
                $category = 'Operating';
            } else if ($coa->type == 'aset') {
                if (str_starts_with($coa->code, '1.05') || str_starts_with($coa->code, '1.06')) {
                    $category = 'Investing';
                } else {
                    $category = 'Operating';
                }
            } else if ($coa->type == 'hutang') {
                if (str_starts_with($coa->code, '2.02') || str_starts_with($coa->code, '2.03')) {
                    $category = 'Financing';
                } else {
                    $category = 'Operating';
                }
            } else if ($coa->type == 'modal') {
                $category = 'Financing';
            }

            $desc = $coa->name;
            if ($category === 'Operating') {
                if (!isset($operating[$desc])) $operating[$desc] = 0;
                $operating[$desc] += $amount;
            } else if ($category === 'Investing') {
                if (!isset($investing[$desc])) $investing[$desc] = 0;
                $investing[$desc] += $amount;
            } else if ($category === 'Financing') {
                if (!isset($financing[$desc])) $financing[$desc] = 0;
                $financing[$desc] += $amount;
            }
        }

        $formatActivities = function($arr) {
            $res = [];
            foreach($arr as $desc => $amount) {
                $res[] = ['description' => $desc, 'amount' => $amount];
            }
            return $res;
        };

        $operatingActivities = $formatActivities($operating);
        $investingActivities = $formatActivities($investing);
        $financingActivities = $formatActivities($financing);

        $operatingTotal = array_sum($operating);
        $investingTotal = array_sum($investing);
        $financingTotal = array_sum($financing);
        
        $netIncrease = $operatingTotal + $investingTotal + $financingTotal;
        $endingCash = $beginningCash + $netIncrease;

        $data = [
            'filters' => [
                'start_date' => $startDate, 'end_date' => $endDate,
                'level' => $level, 'show_zero' => $showZero, 'show_code' => $showCode
            ],
            'maxLevel' => 5,
            'operatingActivities' => $operatingActivities,
            'investingActivities' => $investingActivities,
            'financingActivities' => $financingActivities,
            'operatingTotal' => $operatingTotal,
            'investingTotal' => $investingTotal,
            'financingTotal' => $financingTotal,
            'netIncrease' => $netIncrease,
            'beginningCash' => $beginningCash,
            'endingCash' => $endingCash,
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

    public function settings()
    {
        $coas = Coa::where('is_header', false)->orderBy('code')->get();
        $settings = [
            'retained_earnings_coa_id' => \App\Models\Setting::get('retained_earnings_coa_id'),
            'current_earnings_coa_id' => \App\Models\Setting::get('current_earnings_coa_id'),
        ];

        return Inertia::render('Accounting/Settings/Index', [
            'coas' => $coas,
            'settings' => $settings,
        ]);
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'retained_earnings_coa_id' => 'nullable|exists:coas,id',
            'current_earnings_coa_id' => 'nullable|exists:coas,id',
        ]);

        \App\Models\Setting::set('retained_earnings_coa_id', $request->retained_earnings_coa_id);
        \App\Models\Setting::set('current_earnings_coa_id', $request->current_earnings_coa_id);

        return back()->with('success', 'Pengaturan akun laporan keuangan berhasil disimpan.');
    }
}

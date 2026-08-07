<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$startDate = '2026-08-01';
$endDate = '2026-08-31';

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

print_r($operating);
print_r($investing);
print_r($financing);

$opTotal = array_sum($operating);
$invTotal = array_sum($investing);
$finTotal = array_sum($financing);
$net = $opTotal + $invTotal + $finTotal;
$end = $beginningCash + $net;

echo "Beg: $beginningCash\n";
echo "Net: $net\n";
echo "End: $end\n";

<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Coa;
use App\Models\Setting;
use App\Models\JournalItem;
use App\Services\FinancialReportService;
use Carbon\Carbon;

$asOfDate = Carbon::now()->format('Y-m-d');
$startOfYear = Carbon::parse($asOfDate)->startOfYear()->format('Y-m-d');

// Find first equity COA to test
// $modalCoas = Coa::where('type', 'modal')->where('is_header', false)->get();
// if ($modalCoas->count() >= 2) {
//    Setting::set('retained_earnings_coa_id', $modalCoas[0]->id);
//    Setting::set('current_earnings_coa_id', $modalCoas[1]->id);
// }

$retainedEarningsCoaId = Setting::get('retained_earnings_coa_id');
$currentEarningsCoaId = Setting::get('current_earnings_coa_id');

echo "Retained Earnings COA ID: " . var_export($retainedEarningsCoaId, true) . "\n";
echo "Current Earnings COA ID: " . var_export($currentEarningsCoaId, true) . "\n";

echo "All Settings:\n";
foreach (\App\Models\Setting::all() as $s) {
    echo "- {$s->key}: {$s->value}\n";
}

echo "COA Types:\n";
foreach (Coa::select('type', DB::raw('count(*) as count'))->groupBy('type')->get() as $t) {
    echo "- {$t->type}: {$t->count}\n";
}

$revenueCoas = Coa::where('type', 'pendapatan')->get()->pluck('id');
$expenseCoas = Coa::where('type', 'beban')->get()->pluck('id');

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

echo "Current Year Revenue: " . $currentYearRevenue . "\n";
echo "Current Year Expense: " . $currentYearExpense . "\n";
echo "Current Year Earnings: " . $currentYearEarnings . "\n";

$service = app(FinancialReportService::class);
$reportData = $service->getCoaTreeWithBalances(null, null, $asOfDate, 5, false);
$coas = collect($reportData['flat']);

$equitiesItems = $coas->filter(function($c) {
    return $c->type === 'modal';
})->values()->toArray();

$retainedEarningsCoa = $retainedEarningsCoaId ? Coa::find($retainedEarningsCoaId) : null;
$currentEarningsCoa = $currentEarningsCoaId ? Coa::find($currentEarningsCoaId) : null;

// Calculate Retained Earnings (previous years)
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

$hasRetainedEarningsCoa = false;
$hasCurrentEarningsCoa = false;

foreach ($equitiesItems as &$item) {
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
    $equitiesItems[] = [
        'id' => $retainedEarningsCoa->id,
        'code' => $retainedEarningsCoa->code,
        'name' => $retainedEarningsCoa->name,
        'type' => 'modal',
        'balance' => $retainedEarnings,
        'level' => 2,
        'is_header' => false
    ];
}

if ($currentEarningsCoa && !$hasCurrentEarningsCoa) {
    $equitiesItems[] = [
        'id' => $currentEarningsCoa->id,
        'code' => $currentEarningsCoa->code,
        'name' => $currentEarningsCoa->name,
        'type' => 'modal',
        'balance' => $currentYearEarnings,
        'level' => 2,
        'is_header' => false
    ];
}

echo "Equities List in Report (after mapping):\n";
foreach ($equitiesItems as $eq) {
    echo "- [{$eq['id']}] [{$eq['code']}] {$eq['name']} | Balance: {$eq['balance']}\n";
}

echo "\nAll Modal COAs in DB:\n";
foreach (Coa::where('type', 'modal')->get() as $c) {
    echo "- [{$c->id}] [{$c->code}] {$c->name} (is_header: " . ($c->is_header ? 'true' : 'false') . ")\n";
}

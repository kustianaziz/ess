<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (\App\Models\JournalEntry::with('items.coa')->get() as $e) {
    echo "Entry: {$e->journal_number} | Date: {$e->date} | Status: {$e->status} | Desc: {$e->description}\n";
    foreach ($e->items as $i) {
        echo "  - COA: [{$i->coa->code}] {$i->coa->name} | Debit: {$i->debit} | Credit: {$i->credit}\n";
    }
}

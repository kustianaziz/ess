<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$items = \App\Models\JournalItem::with(['coa', 'journalEntry'])->whereHas('coa', function($q) { $q->where('name', 'Biaya Transport'); })->get()->toArray();
print_r($items);

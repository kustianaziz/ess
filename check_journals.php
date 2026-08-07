<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$entries = \App\Models\JournalEntry::get();
foreach($entries as $entry) {
    echo $entry->id . " - " . $entry->description . " - " . $entry->amount . "\n";
}

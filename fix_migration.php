<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
DB::table('migrations')->where('migration', '2026_08_05_095229_add_is_opening_balance_to_journal_entries_table')->delete();
echo "Deleted migration record.\n";

<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$tables = [
    'customers',
    'invoices',
    'invoice_items',
    'invoice_payments',
    'vendors',
    'domains',
    'renewal_requests',
    'vendor_payments'
];

$output = "<?php\n\nnamespace Database\Seeders;\n\nuse Illuminate\Database\Seeder;\nuse Illuminate\Support\Facades\DB;\n\nclass RealDataSeeder extends Seeder\n{\n    public function run(): void\n    {\n        DB::statement('SET FOREIGN_KEY_CHECKS=0;');\n\n";

foreach ($tables as $table) {
    if (!DB::getSchemaBuilder()->hasTable($table)) continue;
    
    $records = DB::table($table)->get()->map(function($item) {
        return (array) $item;
    })->toArray();
    
    if (empty($records)) continue;
    
    $output .= "        // Seed $table\n";
    $output .= "        DB::table('$table')->truncate();\n";
    
    $chunks = array_chunk($records, 50);
    foreach ($chunks as $chunk) {
        $export = var_export($chunk, true);
        // Replace array ( ) with [ ]
        $export = str_replace(['array (', ')'], ['[', ']'], $export);
        $output .= "        DB::table('$table')->insert($export);\n";
    }
    $output .= "\n";
}

$output .= "        DB::statement('SET FOREIGN_KEY_CHECKS=1;');\n    }\n}\n";

file_put_contents(__DIR__ . '/database/seeders/RealDataSeeder.php', $output);
echo "RealDataSeeder created!\n";

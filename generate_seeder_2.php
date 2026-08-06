<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

$tables = [
    'customers',
    'vendors',
    'domains',
    'invoices',
    'invoice_items',
    'invoice_payments',
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
    
    $output .= "        DB::table('$table')->truncate();\n";
    $output .= "        DB::table('$table')->insert([\n";
    
    foreach ($records as $record) {
        $output .= "            [\n";
        foreach ($record as $key => $value) {
            if ($value === null) {
                $output .= "                '$key' => null,\n";
            } elseif (is_numeric($value)) {
                $output .= "                '$key' => $value,\n";
            } else {
                $valStr = addslashes($value);
                $output .= "                '$key' => '$valStr',\n";
            }
        }
        $output .= "            ],\n";
    }
    $output .= "        ]);\n\n";
}

$output .= "        DB::statement('SET FOREIGN_KEY_CHECKS=1;');\n    }\n}\n";

File::put(__DIR__ . '/database/seeders/RealDataSeeder.php', $output);
echo "RealDataSeeder generated successfully!\n";

<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Coa;

$coas = Coa::all();

$prefixes = [
    'aset' => '1',
    'hutang' => '2',
    'modal' => '3',
    'pendapatan' => '4',
    'beban' => '5'
];

foreach ($coas as $coa) {
    $prefix = $prefixes[$coa->type] ?? '9';
    // Replace the first character with the correct prefix, or generate a new one
    $coa->code = $prefix . rand(1000, 9999);
    $coa->save();
}

echo "COA codes updated!\n";

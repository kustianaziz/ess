<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFileType = 'Xlsx';
$inputFileName = __DIR__ . '/LAPORAN KEUANGAN TAHUN 2026.xlsx';

$reader = IOFactory::createReader($inputFileType);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($inputFileName);

$sheetsToInspect = ['KAS', 'BANK 1', 'JURNAL PENYESUAIAN', 'ASET'];

foreach($sheetsToInspect as $name) {
    echo "\n=== Sheet: $name ===\n";
    $sheet = $spreadsheet->getSheetByName($name);
    if (!$sheet) {
        echo "Not found.\n";
        continue;
    }
    
    $data = $sheet->toArray(null, true, true, true); 
    $count = 0;
    foreach ($data as $rowIndex => $rowData) {
        // filter out completely empty rows
        $filtered = array_filter($rowData, function($v) { return $v !== null && $v !== ''; });
        if (!empty($filtered)) {
            echo "Row $rowIndex: ";
            foreach ($filtered as $col => $val) {
                echo "$col: [$val] | ";
            }
            echo "\n";
            $count++;
        }
        if ($count >= 20) {
            echo "... (truncated) ...\n";
            break;
        }
    }
}

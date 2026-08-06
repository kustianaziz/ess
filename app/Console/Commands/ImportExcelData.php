<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Coa;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ImportExcelData extends Command
{
    protected $signature = 'accounting:import-excel';
    protected $description = 'Import financial transactions from LAPORAN KEUANGAN TAHUN 2026.xlsx';

    // Caches
    protected $coas = [];
    protected $headerCoas = [];

    public function handle()
    {
        $inputFileName = base_path('LAPORAN KEUANGAN TAHUN 2026.xlsx');
        if (!file_exists($inputFileName)) {
            $this->error("File not found: " . $inputFileName);
            return;
        }

        $this->info("Initializing COAs...");
        $this->initBaseCoas();

        $this->info("Reading Excel File (this might take a few seconds)...");
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($inputFileName);

        $sheets = [
            'KAS' => 'Kas di Tangan',
            'BANK 1' => 'Bank BRI',
            'BANK 2' => 'Bank BCA',
            'BANK 3' => 'Bank Mandiri',
            'BANK 4' => 'Bank BNI',
            'JURNAL PENYESUAIAN' => null // Special handling
        ];

        // Clean existing journals before importing (since it's a one-time migration)
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        JournalItem::truncate();
        JournalEntry::truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        foreach ($sheets as $sheetName => $bankCoaName) {
            $this->info("Processing sheet: $sheetName");
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (!$sheet) {
                $this->warn("Sheet $sheetName not found, skipping.");
                continue;
            }

            $bankCoa = null;
            if ($bankCoaName) {
                $bankCoa = $this->getOrCreateCoa($bankCoaName, 'Kas & Setara Kas', 'aset', 'debit');
            }

            $data = $sheet->toArray(null, true, true, true);
            $lastDate = '2026-01-01'; // Fallback
            $rowCount = 0;

            foreach ($data as $rowIndex => $row) {
                if ($rowIndex < 6) continue; // Skip headers

                $rawDate = $row['B'];
                $keterangan = trim($row['C']);
                $uraian = trim($row['D']);
                $pemasukan = $this->cleanNumber($row['F']);
                $pengeluaran = $this->cleanNumber($row['G']);

                if (empty($keterangan) && empty($uraian) && $pemasukan == 0 && $pengeluaran == 0) {
                    continue; // Empty row
                }

                // Saldo Awal row (usually row 6)
                if (strtolower($keterangan) === 'saldo awal' || strtolower($uraian) === 'sisa saldo desember 2025' || strtolower($keterangan) === 'saldo tahun 2025') {
                    $saldo = $this->cleanNumber($row['H']);
                    if ($saldo > 0 && $bankCoa) {
                        $modalAwal = $this->getOrCreateCoa('Modal Awal', 'Ekuitas', 'modal', 'credit');
                        $je = JournalEntry::create([
                            'date' => '2025-12-31',
                            'journal_number' => 'SALDOAWAL-' . Str::slug($sheetName),
                            'description' => 'Saldo Awal ' . $sheetName,
                            'created_by' => 1
                        ]);
                        JournalItem::create(['journal_entry_id' => $je->id, 'coa_id' => $bankCoa->id, 'debit' => $saldo, 'credit' => 0]);
                        JournalItem::create(['journal_entry_id' => $je->id, 'coa_id' => $modalAwal->id, 'debit' => 0, 'credit' => $saldo]);
                    }
                    continue;
                }

                if (!empty($rawDate)) {
                    if (is_numeric($rawDate)) {
                        $lastDate = Date::excelToDateTimeObject($rawDate)->format('Y-m-d');
                    } else {
                        try {
                            $lastDate = Carbon::parse($rawDate)->format('Y-m-d');
                        } catch (\Exception $e) {
                            // keep old date
                        }
                    }
                }

                if ($pemasukan == 0 && $pengeluaran == 0) continue;

                $counterpartCoaName = $keterangan ?: 'Uncategorized';
                $isIncome = stripos($counterpartCoaName, 'Pendapatan') !== false;
                
                $counterpartCoa = $this->getOrCreateCoa(
                    $counterpartCoaName, 
                    $isIncome ? 'Pendapatan Usaha' : 'Beban Operasional',
                    $isIncome ? 'pendapatan' : 'beban',
                    $isIncome ? 'credit' : 'debit'
                );

                if ($sheetName === 'JURNAL PENYESUAIAN') {
                    // For adjustments, if it's placed in Pemasukan/Pengeluaran, it's vague.
                    // Usually Pemasukan = Debit to some account, Pengeluaran = Credit.
                    // Without a clear pairing, we skip or handle if provided.
                    // Assuming Keterangan is Debit Account and Dari/Kepada is Credit Account for penyesuaian.
                    $debitAccount = $this->getOrCreateCoa($keterangan ?: 'Penyesuaian', 'Beban Operasional', 'beban', 'debit');
                    $creditAccount = $this->getOrCreateCoa(trim($row['E']) ?: 'Penyesuaian Kredit', 'Kewajiban', 'hutang', 'credit');
                    $amount = $pemasukan > 0 ? $pemasukan : $pengeluaran;

                    if ($amount > 0) {
                        $je = JournalEntry::create([
                            'date' => $lastDate,
                            'journal_number' => 'ADJ-' . $rowIndex,
                            'description' => $uraian ?: 'Jurnal Penyesuaian',
                            'created_by' => 1
                        ]);
                        JournalItem::create(['journal_entry_id' => $je->id, 'coa_id' => $debitAccount->id, 'debit' => $amount, 'credit' => 0]);
                        JournalItem::create(['journal_entry_id' => $je->id, 'coa_id' => $creditAccount->id, 'debit' => 0, 'credit' => $amount]);
                        $rowCount++;
                    }
                    continue;
                }

                // Normal Bank/Kas Transaction
                $je = JournalEntry::create([
                    'date' => $lastDate,
                    'journal_number' => Str::slug($sheetName) . '-' . $rowIndex,
                    'description' => $uraian ?: $keterangan,
                    'created_by' => 1
                ]);

                if ($pemasukan > 0) {
                    // Debit Bank, Credit Counterpart
                    JournalItem::create(['journal_entry_id' => $je->id, 'coa_id' => $bankCoa->id, 'debit' => $pemasukan, 'credit' => 0]);
                    JournalItem::create(['journal_entry_id' => $je->id, 'coa_id' => $counterpartCoa->id, 'debit' => 0, 'credit' => $pemasukan]);
                }

                if ($pengeluaran > 0) {
                    // Credit Bank, Debit Counterpart
                    JournalItem::create(['journal_entry_id' => $je->id, 'coa_id' => $bankCoa->id, 'debit' => 0, 'credit' => $pengeluaran]);
                    JournalItem::create(['journal_entry_id' => $je->id, 'coa_id' => $counterpartCoa->id, 'debit' => $pengeluaran, 'credit' => 0]);
                }
                
                $rowCount++;
            }
            $this->info("Imported $rowCount transactions for $sheetName");
        }

        $this->info("Done! Migration from Excel complete.");
    }

    protected function cleanNumber($val)
    {
        if (!$val) return 0;
        $val = str_replace(',', '', $val);
        return (float) $val;
    }

    protected function initBaseCoas()
    {
        $headers = [
            ['Aset', 'aset', 'debit'],
            ['Kas & Setara Kas', 'aset', 'debit', 'Aset'],
            ['Kewajiban', 'hutang', 'credit'],
            ['Hutang Usaha', 'hutang', 'credit', 'Kewajiban'],
            ['Ekuitas', 'modal', 'credit'],
            ['Modal', 'modal', 'credit', 'Ekuitas'],
            ['Pendapatan', 'pendapatan', 'credit'],
            ['Pendapatan Usaha', 'pendapatan', 'credit', 'Pendapatan'],
            ['Beban', 'beban', 'debit'],
            ['Beban Operasional', 'beban', 'debit', 'Beban'],
        ];

        foreach ($headers as $h) {
            $name = $h[0];
            $type = $h[1];
            $bal = $h[2];
            $parentName = $h[3] ?? null;

            $parent_id = null;
            if ($parentName) {
                $parent_id = Coa::where('name', $parentName)->first()->id ?? null;
            }

            $coa = Coa::firstOrCreate(
                ['name' => $name],
                [
                    'code' => strtoupper(substr($name, 0, 3)) . rand(100,999), // simplistic code
                    'type' => $type,
                    'normal_balance' => $bal,
                    'is_header' => true,
                    'parent_id' => $parent_id,
                    'is_active' => true
                ]
            );
            $this->headerCoas[$name] = $coa;
        }
    }

    protected function getOrCreateCoa($name, $parentHeaderName, $type, $normalBalance)
    {
        if (isset($this->coas[$name])) {
            return $this->coas[$name];
        }

        $coa = Coa::where('name', $name)->first();
        if (!$coa) {
            $parent = $this->headerCoas[$parentHeaderName] ?? Coa::where('name', $parentHeaderName)->first();
            $coa = Coa::create([
                'name' => $name,
                'code' => strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 3)) . rand(1000,9999),
                'type' => $type,
                'normal_balance' => $normalBalance,
                'is_header' => false,
                'parent_id' => $parent ? $parent->id : null,
                'is_active' => true
            ]);
        }
        $this->coas[$name] = $coa;
        return $coa;
    }
}

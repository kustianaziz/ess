<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Coa;

class MasterCoaSeeder extends Seeder
{
    public function run()
    {
        // Truncate existing data to start fresh (make sure no foreign key constraint violations)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Coa::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $coas = [
            // Aset
            ['1.01', 'Kas & Setara Kas', 'aset', 'debit', true, null],
            ['1.01.01', 'Kas', 'aset', 'debit', true, '1.01'],
            ['1.01.01.001', 'Kas Operasional', 'aset', 'debit', false, '1.01.01'],
            ['1.01.02', 'Bank', 'aset', 'debit', true, '1.01'],
            ['1.01.02.001', 'Bank 1 - BRI', 'aset', 'debit', false, '1.01.02'],
            ['1.01.02.002', 'Bank 2', 'aset', 'debit', false, '1.01.02'],
            ['1.01.02.003', 'Bank 3', 'aset', 'debit', false, '1.01.02'],
            ['1.01.02.004', 'Bank 4', 'aset', 'debit', false, '1.01.02'],
            ['1.02', 'Piutang', 'aset', 'debit', true, null],
            ['1.02.01', 'Piutang Usaha', 'aset', 'debit', true, '1.02'],
            ['1.02.01.001', 'Piutang Eduvizta', 'aset', 'debit', false, '1.02.01'],
            ['1.02.01.002', 'Piutang Edu Sinergi', 'aset', 'debit', false, '1.02.01'],
            ['1.02.01.003', 'Piutang Usaha Lainnya', 'aset', 'debit', false, '1.02.01'],
            ['1.02.02', 'Piutang Lainnya', 'aset', 'debit', true, '1.02'],
            ['1.02.02.001', 'Piutang Karyawan', 'aset', 'debit', false, '1.02.02'],
            ['1.03', 'Persediaan', 'aset', 'debit', true, null],
            ['1.04', 'Pajak Dibayar Dimuka', 'aset', 'debit', true, null],
            ['1.04.01', 'PPh 23 Dibayar Dimuka', 'aset', 'debit', false, '1.04'],
            ['1.04.02', 'PPh 22 Dibayar Dimuka', 'aset', 'debit', false, '1.04'],
            ['1.04.03', 'PPh 25 Dibayar Dimuka', 'aset', 'debit', false, '1.04'],
            ['1.04.04', 'PPN Masukan', 'aset', 'debit', false, '1.04'],
            ['1.04.05', 'PPN Dibayar Dimuka (Instansi)', 'aset', 'debit', false, '1.04'],
            ['1.05', 'Aset Tetap', 'aset', 'debit', true, null],
            ['1.05.01', 'Tanah', 'aset', 'debit', false, '1.05'],
            ['1.05.02', 'Gedung & Bangunan', 'aset', 'debit', false, '1.05'],
            ['1.05.03', 'Kendaraan', 'aset', 'debit', false, '1.05'],
            ['1.05.04', 'Peralatan Kantor', 'aset', 'debit', false, '1.05'],
            ['1.05.05', 'Mesin', 'aset', 'debit', false, '1.05'],
            ['1.05.06', 'Perangkat IT & Elektronik', 'aset', 'debit', false, '1.05'],
            ['1.06', 'Akumulasi Penyusutan Aset Tetap', 'aset', 'debit', true, null],
            ['1.06.01', 'AKM. Tanah', 'aset', 'debit', false, '1.06'],
            ['1.06.02', 'AKM. Gedung & Bangunan', 'aset', 'debit', false, '1.06'],
            ['1.06.03', 'AKM. Kendaraan', 'aset', 'debit', false, '1.06'],
            ['1.06.04', 'AKM. Peralatan Kantor', 'aset', 'debit', false, '1.06'],
            ['1.06.05', 'AKM. Mesin', 'aset', 'debit', false, '1.06'],
            ['1.06.06', 'AKM. Perangkat IT & Elektronik', 'aset', 'debit', false, '1.06'],
            ['1.07', 'Prive/Dividen', 'aset', 'debit', true, null],
            ['1.07.01', 'Owner', 'aset', 'debit', false, '1.07'],
        
            // Hutang
            ['2.01', 'Hutang Usaha', 'hutang', 'credit', true, null],
            ['2.01.01', 'Hutang Usaha', 'hutang', 'credit', false, '2.01'],
            ['2.01.02', 'Hutang Usaha Lainnya', 'hutang', 'credit', false, '2.01'],
            ['2.02', 'PPN Keluaran', 'hutang', 'credit', true, null],
            ['2.02.01', 'PPN Eduvizta', 'hutang', 'credit', false, '2.02'],
            ['2.02.02', 'PPN Edu Sinergi', 'hutang', 'credit', false, '2.02'],
            ['2.02.03', 'PPN Web Praktis', 'hutang', 'credit', false, '2.02'],
        
            // Modal
            ['3.01', 'Modal', 'modal', 'credit', true, null],
            ['3.01.01', 'Modal', 'modal', 'credit', false, '3.01'],
            ['3.02', 'Laba Ditahan', 'modal', 'credit', true, null],
            ['3.02.01', 'Laba Ditahan', 'modal', 'credit', false, '3.02'],
            ['3.02.02', 'Laba Ditahan Tahun Berjalan', 'modal', 'credit', false, '3.02'],
        
            // Pendapatan
            ['4.01', 'Pendapatan Usaha', 'pendapatan', 'credit', true, null],
            ['4.01.01', 'Pendapatan Eduvizta', 'pendapatan', 'credit', false, '4.01'],
            ['4.01.02', 'Pendapatan Edu Sinergi Informatika', 'pendapatan', 'credit', false, '4.01'],
            ['4.01.03', 'Pendapatan Webpraktis', 'pendapatan', 'credit', false, '4.01'],
            ['4.01.04', 'Pendapatan usaha lainnya', 'pendapatan', 'credit', false, '4.01'],
            ['4.01.05', 'Pendapatan Edu Media Digital', 'pendapatan', 'credit', false, '4.01'],
            ['4.02', 'Pendapatan Luar Usaha (PLU)', 'pendapatan', 'credit', true, null],
        
            // Beban
            ['5.01', 'Beban Pokok Penjualan / Jasa', 'beban', 'debit', true, null],
            ['5.01.01', 'Pembelian', 'beban', 'debit', false, '5.01'],
            ['5.01.02', 'Harga Pokok Penjualan (HPP)', 'beban', 'debit', false, '5.01'],
            ['5.01.03', 'Biaya Sehubungan dengan Jasa', 'beban', 'debit', false, '5.01'],
            ['5.02', 'Beban Kepegawaian', 'beban', 'debit', true, null],
            ['5.02.01', 'Biaya Gaji Karyawan', 'beban', 'debit', false, '5.02'],
            ['5.02.02', 'Biaya BPJS', 'beban', 'debit', false, '5.02'],
            ['5.03', 'Beban Pemasaran', 'beban', 'debit', true, null],
            ['5.03.01', 'Biaya Pemasaran', 'beban', 'debit', false, '5.03'],
            ['5.04', 'Beban Operasional & Umum', 'beban', 'debit', true, null],
            ['5.04.01', 'Biaya Listrik, Internet, PDAM', 'beban', 'debit', false, '5.04'],
            ['5.04.02', 'Biaya Operasional', 'beban', 'debit', false, '5.04'],
            ['5.04.03', 'Biaya Transport', 'beban', 'debit', false, '5.04'],
            ['5.04.04', 'Biaya Pemeliharaan', 'beban', 'debit', false, '5.04'],
            ['5.04.05', 'Biaya Sewa', 'beban', 'debit', false, '5.04'],
            ['5.04.06', 'Biaya Administrasi & Umum', 'beban', 'debit', false, '5.04'],
            ['5.04.07', 'Biaya Depresiasi/Penyusutan', 'beban', 'debit', false, '5.04'],
            ['5.05', 'Beban Pajak', 'beban', 'debit', true, null],
            ['5.05.01', 'Biaya Pajak', 'beban', 'debit', false, '5.05'],
            ['5.06', 'Beban Luar Usaha', 'beban', 'debit', true, null],
            ['5.06.01', 'Biaya Luar Usaha (BLU)', 'beban', 'debit', false, '5.06'],
        ];

        // Store ID mapping to handle parent_id relations
        $idMap = [];

        foreach ($coas as $coaData) {
            $parentCode = $coaData[5];
            $parentId = $parentCode ? ($idMap[$parentCode] ?? null) : null;

            $coa = Coa::create([
                'code' => $coaData[0],
                'name' => $coaData[1],
                'type' => $coaData[2],
                'normal_balance' => $coaData[3],
                'is_header' => $coaData[4],
                'parent_id' => $parentId,
                'is_active' => true,
            ]);

            $idMap[$coaData[0]] = $coa->id;
        }

        $this->command->info('Master COA seeded successfully.');
    }
}

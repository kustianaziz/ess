<?php

namespace Database\Seeders;

use App\Models\ExpenseType;
use Illuminate\Database\Seeder;

class ExpenseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Transportasi',
            'Konsumsi',
            'Perlengkapan Kantor',
            'Kesehatan',
            'Akomodasi & Perjalanan Dinas',
            'Lainnya',
        ];

        foreach ($types as $type) {
            ExpenseType::firstOrCreate(['name' => $type], ['is_active' => true]);
        }
    }
}

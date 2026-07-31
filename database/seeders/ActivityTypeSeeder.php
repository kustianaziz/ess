<?php

namespace Database\Seeders;

use App\Models\ActivityType;
use Illuminate\Database\Seeder;

class ActivityTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Rapat Internal',
            'Kunjungan Klien',
            'Acara Perusahaan',
            'Pelatihan & Workshop',
            'Kegiatan Divisi',
            'Operasional Lapangan',
        ];

        foreach ($types as $type) {
            ActivityType::firstOrCreate(['name' => $type], ['is_active' => true]);
        }
    }
}

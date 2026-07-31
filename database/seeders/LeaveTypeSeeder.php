<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Cuti Tahunan',
                'default_quota' => 12,
                'requires_attachment' => false,
            ],
            [
                'name' => 'Cuti Sakit',
                'default_quota' => 14,
                'requires_attachment' => true,
            ],
            [
                'name' => 'Cuti Melahirkan',
                'default_quota' => 90,
                'requires_attachment' => true,
            ],
            [
                'name' => 'Cuti Menikah',
                'default_quota' => 3,
                'requires_attachment' => false,
            ],
            [
                'name' => 'Cuti Duka',
                'default_quota' => 2,
                'requires_attachment' => false,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(['name' => $type['name']], array_merge($type, ['is_active' => true]));
        }
    }
}

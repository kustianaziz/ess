<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            ['name' => 'IT Department', 'code' => 'IT'],
            ['name' => 'Finance & Accounting', 'code' => 'FA'],
            ['name' => 'Human Resources & Legal', 'code' => 'HR'],
            ['name' => 'Marketing & Sales', 'code' => 'MKT'],
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'General Affairs', 'code' => 'GA'],
        ];

        foreach ($divisions as $division) {
            Division::firstOrCreate(['name' => $division['name']], $division);
        }
    }
}

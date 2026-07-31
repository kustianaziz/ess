<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Division;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $itDept = Division::where('code', 'IT')->first();
        $hrDept = Division::where('code', 'HR')->first();
        $finDept = Division::where('code', 'FIN')->first();
        $opsDept = Division::where('code', 'OPS')->first();

        // 1. Admin User (DO NOT DELETE)
        $admin = User::updateOrCreate(
            ['email' => 'admin@edu.id'],
            [
                'nik' => 'EDU-ADM-001',
                'name' => 'System Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'division_id' => $itDept?->id,
                'position' => 'System Administrator',
                'phone' => '081234567890',
                'status' => 'active',
            ]
        );
        $admin->syncRoles([UserRole::ADMIN->value]);

        // 2. Direktur / Manager Level 1 (Ucu Komarudin)
        $direktur = User::updateOrCreate(
            ['email' => 'ucu@edu.id'],
            [
                'nik' => 'EDU-DIR-001',
                'name' => 'ucu komarudin',
                'username' => 'ucu',
                'password' => Hash::make('password'),
                'division_id' => $opsDept?->id ?? $itDept?->id,
                'position' => 'Direktur',
                'phone' => '081234567891',
                'status' => 'active',
            ]
        );
        $direktur->syncRoles([UserRole::MANAGER->value]);

        // 3. HRD dan Keuangan (Ai Siti Nuralisah & Fourizal Noviansyah)
        $aisiti = User::updateOrCreate(
            ['email' => 'aisiti@edu.id'],
            [
                'nik' => 'EDU-HRD-001',
                'name' => 'ai siti nuralisah',
                'username' => 'aisiti',
                'password' => Hash::make('password'),
                'division_id' => $hrDept?->id,
                'position' => 'HRD dan Keuangan',
                'phone' => '081234567892',
                'status' => 'active',
            ]
        );
        $aisiti->syncRoles([UserRole::HRD_FINANCE->value]);

        $opi = User::updateOrCreate(
            ['email' => 'opi@edu.id'],
            [
                'nik' => 'EDU-HRD-002',
                'name' => 'Fourizal noviansyah',
                'username' => 'opi',
                'password' => Hash::make('password'),
                'division_id' => $finDept?->id ?? $hrDept?->id,
                'position' => 'HRD dan Keuangan',
                'phone' => '081234567893',
                'status' => 'active',
            ]
        );
        $opi->syncRoles([UserRole::HRD_FINANCE->value]);

        // 4. List Karyawan
        $karyawanList = [
            ['name' => 'Kustiani Abdul Aziz', 'username' => 'kustian', 'nik' => 'EDU-EMP-001'],
            ['name' => 'Saca Sunantara', 'username' => 'saca', 'nik' => 'EDU-EMP-002'],
            ['name' => 'bakti atma', 'username' => 'bakti', 'nik' => 'EDU-EMP-003'],
            ['name' => 'Mutiara Nanda P', 'username' => 'muti', 'nik' => 'EDU-EMP-004'],
            ['name' => 'nova novianti', 'username' => 'nova', 'nik' => 'EDU-EMP-005'],
            ['name' => 'maudy tasya v', 'username' => 'maudy', 'nik' => 'EDU-EMP-006'],
            ['name' => 'Dial Rubiat', 'username' => 'dial', 'nik' => 'EDU-EMP-007'],
            ['name' => 'Djajang Jananto', 'username' => 'jajang', 'nik' => 'EDU-EMP-008'],
            ['name' => 'aditya', 'username' => 'adit', 'nik' => 'EDU-EMP-009'],
            ['name' => 'Raka putra', 'username' => 'raka', 'nik' => 'EDU-EMP-010'],
            ['name' => 'raka zakaria', 'username' => 'rakjun', 'nik' => 'EDU-EMP-011'],
            ['name' => 'azka', 'username' => 'azka', 'nik' => 'EDU-EMP-012'],
            ['name' => 'Meira Nurul Avivah', 'username' => 'rara', 'nik' => 'EDU-EMP-013'],
            ['name' => 'febianto', 'username' => 'febian', 'nik' => 'EDU-EMP-014'],
            ['name' => 'aufa', 'username' => 'aufa', 'nik' => 'EDU-EMP-015'],
        ];

        $leaveTypes = LeaveType::all();

        foreach ($karyawanList as $k) {
            $user = User::updateOrCreate(
                ['username' => $k['username']],
                [
                    'email' => $k['username'] . '@edu.id',
                    'nik' => $k['nik'],
                    'name' => $k['name'],
                    'password' => Hash::make('password'),
                    'division_id' => $itDept?->id,
                    'position' => 'Karyawan',
                    'phone' => '0812' . rand(10000000, 99999999),
                    'manager_id' => $direktur->id,
                    'hire_date' => '2023-01-01',
                    'status' => 'active',
                ]
            );
            $user->syncRoles([UserRole::EMPLOYEE->value]);

            // Seed leave balances for each employee
            foreach ($leaveTypes as $type) {
                LeaveBalance::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'leave_type_id' => $type->id,
                        'year' => date('Y'),
                    ],
                    [
                        'quota' => $type->default_quota ?? 12,
                        'used' => 0,
                        'remaining' => $type->default_quota ?? 12,
                    ]
                );
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\CashAccount;
use App\Models\MonthlyBillType;
use Illuminate\Database\Seeder;

class CashOperationalSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Cash Accounts
        $account1 = CashAccount::firstOrCreate(
            ['code' => 'KAS-PST'],
            [
                'name' => 'Kas Operasional Pusat',
                'current_balance' => 50000000, // Default seed balance Rp 50.000.000
                'is_active' => true,
            ]
        );

        $account2 = CashAccount::firstOrCreate(
            ['code' => 'KAS-KCL'],
            [
                'name' => 'Kas Kecil (Petty Cash)',
                'current_balance' => 5000000, // Default seed balance Rp 5.000.000
                'is_active' => true,
            ]
        );

        // 2. Monthly Bill Types
        MonthlyBillType::firstOrCreate(
            ['name' => 'Listrik PLN Kantor Pusat'],
            [
                'vendor_name' => 'PT PLN (Persero)',
                'default_amount' => 3500000,
                'billing_day' => 10,
                'cash_account_id' => $account1->id,
                'is_active' => true,
            ]
        );

        MonthlyBillType::firstOrCreate(
            ['name' => 'Internet Dedicated Biznet / Indihome'],
            [
                'vendor_name' => 'Biznet Networks',
                'default_amount' => 2500000,
                'billing_day' => 15,
                'cash_account_id' => $account1->id,
                'is_active' => true,
            ]
        );

        MonthlyBillType::firstOrCreate(
            ['name' => 'Parkir Bulanan Gedung & Operasional'],
            [
                'vendor_name' => 'Pengelola Gedung',
                'default_amount' => 1200000,
                'billing_day' => 20,
                'cash_account_id' => $account2->id,
                'is_active' => true,
            ]
        );

        MonthlyBillType::firstOrCreate(
            ['name' => 'Iuran Kebersihan & Keamanan Lingkungan'],
            [
                'vendor_name' => 'Pengurus RT/RW / Kawasan',
                'default_amount' => 500000,
                'billing_day' => 5,
                'cash_account_id' => $account2->id,
                'is_active' => true,
            ]
        );
    }
}

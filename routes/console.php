<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('transactions:clear', function () {
    Schema::disableForeignKeyConstraints();
    DB::table('reimbursement_requests')->truncate();
    DB::table('operational_requests')->truncate();
    DB::table('leave_requests')->truncate();
    DB::table('approvals')->truncate();
    DB::table('status_histories')->truncate();
    DB::table('attachments')->truncate();
    DB::table('notifications')->truncate();
    Schema::enableForeignKeyConstraints();
    $this->info('Seluruh data transaksi dan notifikasi telah berhasil dibersihkan.');
})->purpose('Membersihkan seluruh data transaksi dan notifikasi.');

use Illuminate\Support\Facades\Schedule;
Schedule::command('requests:complete-finished')->daily();


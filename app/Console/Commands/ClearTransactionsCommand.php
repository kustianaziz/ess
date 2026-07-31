<?php

namespace App\Console\Commands;

use App\Models\Approval;
use App\Models\Attachment;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\OperationalRequest;
use App\Models\ReimbursementRequest;
use App\Models\StatusHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearTransactionsCommand extends Command
{
    protected $signature = 'transactions:clear';
    protected $description = 'Bersihkan seluruh data transaksi pengajuan, approval, lampiran, riwayat status, dan notifikasi.';

    public function handle(): int
    {
        $this->info('Membersihkan data transaksi...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('reimbursement_requests')->truncate();
        DB::table('operational_requests')->truncate();
        DB::table('leave_requests')->truncate();
        DB::table('attachments')->truncate();
        DB::table('approvals')->truncate();
        DB::table('status_histories')->truncate();
        DB::table('notifications')->truncate();

        // Reset Leave Balances
        DB::table('leave_balances')->update([
            'used' => 0,
            'remaining' => DB::raw('quota'),
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Seluruh data transaksi pengajuan berhasil dibersihkan!');
        return Command::SUCCESS;
    }
}

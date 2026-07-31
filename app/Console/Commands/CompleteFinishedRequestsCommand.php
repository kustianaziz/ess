<?php

namespace App\Console\Commands;

use App\Actions\Shared\RecordStatusHistoryAction;
use App\Enums\RequestStatus;
use App\Models\LeaveRequest;
use App\Models\OperationalRequest;
use App\Models\ReimbursementRequest;
use Illuminate\Console\Command;

class CompleteFinishedRequestsCommand extends Command
{
    protected $signature = 'requests:complete-finished';
    protected $description = 'Otomatis mengubah status pengajuan menjadi Selesai (Completed) setelah cuti berakhir atau pembayaran berlalu.';

    public function handle(RecordStatusHistoryAction $recordHistory): int
    {
        $this->info('Memulai pembaruan status pengajuan otomatis...');
        $count = 0;

        // 1. Leave Requests: Approved and end_date passed
        $leaveRequests = LeaveRequest::where('status', RequestStatus::APPROVED->value)
            ->where('end_date', '<', now()->toDateString())
            ->get();

        foreach ($leaveRequests as $req) {
            $oldStatus = $req->status->value;
            $req->update(['status' => RequestStatus::COMPLETED->value]);
            $recordHistory->execute($req, $oldStatus, RequestStatus::COMPLETED->value, null, 'Status otomatis diperbarui menjadi Selesai (Sistem Scheduler).');
            $count++;
        }

        // 2. Reimbursements: Paid > 3 days ago
        $reimbursements = ReimbursementRequest::where('status', RequestStatus::PAID->value)
            ->where('paid_at', '<=', now()->subDays(3))
            ->get();

        foreach ($reimbursements as $req) {
            $oldStatus = $req->status->value;
            $req->update(['status' => RequestStatus::COMPLETED->value]);
            $recordHistory->execute($req, $oldStatus, RequestStatus::COMPLETED->value, null, 'Status otomatis diperbarui menjadi Selesai (Sistem Scheduler).');
            $count++;
        }

        // 3. Operational: Paid > 3 days ago
        $operationals = OperationalRequest::where('status', RequestStatus::PAID->value)
            ->where('paid_at', '<=', now()->subDays(3))
            ->get();

        foreach ($operationals as $req) {
            $oldStatus = $req->status->value;
            $req->update(['status' => RequestStatus::COMPLETED->value]);
            $recordHistory->execute($req, $oldStatus, RequestStatus::COMPLETED->value, null, 'Status otomatis diperbarui menjadi Selesai (Sistem Scheduler).');
            $count++;
        }

        $this->info("Pembaruan selesai. Total {$count} pengajuan telah diperbarui ke status Selesai.");
        return Command::SUCCESS;
    }
}

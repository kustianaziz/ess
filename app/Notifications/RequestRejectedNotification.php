<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestRejectedNotification extends Notification
{
    use Queueable;

    public string $type;
    public int $requestId;
    public string $requestNumber;
    public string $reason;

    public function __construct(string $type, int $requestId, string $requestNumber, string $reason)
    {
        $this->type = $type;
        $this->requestId = $requestId;
        $this->requestNumber = $requestNumber;
        $this->reason = $reason;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $typeLabel = match($this->type) {
            'reimbursement' => 'Reimbursement',
            'operasional' => 'Konsumsi / Operasional',
            'cuti' => 'Cuti',
            default => 'Pengajuan',
        };

        return [
            'title' => 'Pengajuan Ditolak',
            'message' => "Pengajuan {$typeLabel} ({$this->requestNumber}) Anda ditolak. Alasan: {$this->reason}",
            'related_type' => $this->type,
            'related_id' => $this->requestId,
            'url' => "/riwayat-pengajuan/{$this->type}/{$this->requestId}",
        ];
    }
}

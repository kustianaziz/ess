<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestApprovedNotification extends Notification
{
    use Queueable;

    public string $type;
    public int $requestId;
    public string $requestNumber;
    public int $level;

    public function __construct(string $type, int $requestId, string $requestNumber, int $level = 2)
    {
        $this->type = $type;
        $this->requestId = $requestId;
        $this->requestNumber = $requestNumber;
        $this->level = $level;
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

        $msg = $this->level === 1 
            ? "Pengajuan {$typeLabel} ({$this->requestNumber}) telah disetujui Atasan (Level 1) dan diteruskan ke HRD/Finance."
            : "Selamat! Pengajuan {$typeLabel} ({$this->requestNumber}) Anda telah disetujui sepenuhnya.";

        return [
            'title' => 'Pengajuan Disetujui',
            'message' => $msg,
            'related_type' => $this->type,
            'related_id' => $this->requestId,
            'url' => "/riwayat-pengajuan/{$this->type}/{$this->requestId}",
        ];
    }
}

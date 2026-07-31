<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestSubmittedNotification extends Notification
{
    use Queueable;

    public string $type;
    public int $requestId;
    public string $requestNumber;
    public string $applicantName;

    public function __construct(string $type, int $requestId, string $requestNumber, string $applicantName)
    {
        $this->type = $type;
        $this->requestId = $requestId;
        $this->requestNumber = $requestNumber;
        $this->applicantName = $applicantName;
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
            'title' => 'Pengajuan Baru Perlu Persetujuan',
            'message' => "Pengajuan {$typeLabel} ({$this->requestNumber}) dari {$this->applicantName} membutuhkan persetujuan Anda.",
            'related_type' => $this->type,
            'related_id' => $this->requestId,
            'url' => '/approval',
        ];
    }
}

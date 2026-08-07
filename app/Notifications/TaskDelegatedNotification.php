<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDelegatedNotification extends Notification
{
    use Queueable;

    public string $type;
    public int $requestId;
    public string $requestNumber;
    public string $assignerName;

    public function __construct(string $type, int $requestId, string $requestNumber, string $assignerName)
    {
        $this->type = $type;
        $this->requestId = $requestId;
        $this->requestNumber = $requestNumber;
        $this->assignerName = $assignerName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
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
            'perjalanan-dinas' => 'Perjalanan Dinas',
            default => 'Pengajuan',
        };

        return [
            'title' => 'Penugasan Baru',
            'message' => "Anda telah ditugaskan untuk {$typeLabel} ({$this->requestNumber}) oleh {$this->assignerName}.",
            'related_type' => $this->type,
            'related_id' => $this->requestId,
            'url' => '/riwayat-pengajuan',
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

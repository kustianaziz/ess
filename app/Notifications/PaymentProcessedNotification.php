<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentProcessedNotification extends Notification
{
    use Queueable;

    public string $type;
    public int $requestId;
    public string $requestNumber;
    public string $reference;

    public function __construct(string $type, int $requestId, string $requestNumber, string $reference)
    {
        $this->type = $type;
        $this->requestId = $requestId;
        $this->requestNumber = $requestNumber;
        $this->reference = $reference;
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
            default => 'Pengajuan',
        };

        return [
            'title' => 'Pembayaran Berhasil Diproses',
            'message' => "Pembayaran pengajuan {$typeLabel} ({$this->requestNumber}) telah diproses oleh HRD/Finance dengan Ref: {$this->reference}.",
            'related_type' => $this->type,
            'related_id' => $this->requestId,
            'url' => "/riwayat-pengajuan/{$this->type}/{$this->requestId}",
        ];
    }
}

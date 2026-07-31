<?php

namespace App\Http\Controllers;

use App\Actions\Shared\RecordStatusHistoryAction;
use App\Enums\RequestStatus;
use App\Models\OperationalRequest;
use App\Models\ReimbursementRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function process(Request $request, string $type, int $id, RecordStatusHistoryAction $recordHistory): RedirectResponse
    {
        $request->validate([
            'payment_reference' => 'required|string|max:100',
        ]);

        $user = $request->user();
        $reference = $request->input('payment_reference');

        return DB::transaction(function() use ($type, $id, $user, $reference, $recordHistory) {
            $model = match($type) {
                'reimbursement' => ReimbursementRequest::findOrFail($id),
                'operasional' => OperationalRequest::findOrFail($id),
                default => abort(404),
            };

            $oldStatus = $model->status->value;
            $model->update([
                'status' => RequestStatus::PAID->value,
                'paid_at' => now(),
                'paid_by' => $user->id,
                'payment_reference' => $reference,
            ]);

            $recordHistory->execute($model, $oldStatus, RequestStatus::PAID->value, $user->id, "Pembayaran berhasil diproses dengan No. Referensi Transfer: {$reference}");

            // Notify applicant
            $model->user?->notify(new \App\Notifications\PaymentProcessedNotification($type, $model->id, $model->request_number, $reference));

            return back()->with('success', 'Pembayaran berhasil diproses dan status diperbarui menjadi Sudah Dibayarkan.');
        });
    }
}

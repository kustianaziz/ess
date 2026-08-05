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
    public function process(
        Request $request,
        string $type,
        int $id,
        RecordStatusHistoryAction $recordHistory,
        \App\Actions\CashOperational\RecordCashTransactionAction $recordCashTransaction
    ): RedirectResponse {
        $validated = $request->validate([
            'payment_reference' => 'required|string|max:100',
            'disbursed_budget' => 'nullable|numeric|min:0',
            'allowance_breakdown' => 'nullable|array',
        ]);

        $user = $request->user();
        $reference = $validated['payment_reference'];

        return DB::transaction(function() use ($type, $id, $user, $reference, $validated, $recordHistory, $recordCashTransaction) {
            $model = match($type) {
                'reimbursement' => ReimbursementRequest::findOrFail($id),
                'operasional' => OperationalRequest::findOrFail($id),
                'perjalanan-dinas' => \App\Models\BusinessTripRequest::findOrFail($id),
                default => abort(404),
            };

            $oldStatus = $model->status->value;
            $updateData = [
                'status' => RequestStatus::PAID->value,
                'paid_at' => now(),
                'paid_by' => $user->id,
                'payment_reference' => $reference,
            ];

            $amount = match($type) {
                'reimbursement' => (float)$model->amount,
                'operasional' => (float)$model->estimated_cost,
                'perjalanan-dinas' => (float)($validated['disbursed_budget'] ?? $model->estimated_budget),
                default => 0,
            };

            $category = match($type) {
                'reimbursement' => 'reimburse',
                'operasional' => 'operasional_lain',
                'perjalanan-dinas' => 'perjalanan_dinas',
                default => 'lainnya',
            };

            if ($type === 'perjalanan-dinas') {
                $updateData['disbursed_budget'] = $amount;
                if (!empty($validated['allowance_breakdown'])) {
                    $updateData['allowance_breakdown'] = $validated['allowance_breakdown'];
                }
            }

            $model->update($updateData);

            // Record cash out transaction automatically
            $primaryAccount = \App\Models\CashAccount::where('is_active', true)->first();
            if ($primaryAccount && $amount > 0) {
                $recordCashTransaction->execute(
                    $primaryAccount->id,
                    'out',
                    $category,
                    $amount,
                    "Pencairan {$model->request_number} (Ref: {$reference})",
                    $user->id,
                    $model
                );
            }

            $recordHistory->execute($model, $oldStatus, RequestStatus::PAID->value, $user->id, "Pembayaran berhasil diproses dengan No. Referensi Transfer: {$reference}");

            // Notify applicant
            $model->user?->notify(new \App\Notifications\PaymentProcessedNotification($type, $model->id, $model->request_number, $reference));

            return back()->with('success', 'Pembayaran berhasil diproses dan mutasi kas berhasil dicatat!');
        });
    }
}

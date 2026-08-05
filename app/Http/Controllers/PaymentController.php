<?php

namespace App\Http\Controllers;

use App\Actions\Shared\RecordStatusHistoryAction;
use App\Enums\RequestStatus;
use App\Models\CashAccount;
use App\Models\OperationalRequest;
use App\Models\ReimbursementRequest;
use App\Models\BusinessTripRequest;
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
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'disbursed_budget' => 'nullable|numeric|min:0',
            'allowance_breakdown' => 'nullable|array',
            'proof_of_payment' => 'nullable',
            'proof_of_payment.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user = $request->user();
        $reference = $validated['payment_reference'];
        $cashAccountId = (int) $validated['cash_account_id'];

        return DB::transaction(function() use ($type, $id, $user, $reference, $cashAccountId, $validated, $request, $recordHistory, $recordCashTransaction) {
            $model = match($type) {
                'reimbursement' => ReimbursementRequest::findOrFail($id),
                'operasional' => OperationalRequest::findOrFail($id),
                'perjalanan-dinas' => BusinessTripRequest::findOrFail($id),
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

            // Handle Proof of Payment Upload if uploaded (supports single or array of multiple files)
            if ($request->hasFile('proof_of_payment')) {
                $files = is_array($request->file('proof_of_payment')) 
                    ? $request->file('proof_of_payment') 
                    : [$request->file('proof_of_payment')];

                foreach ($files as $idx => $file) {
                    if ($file && $file->isValid()) {
                        $path = $file->store('proofs', 'public');
                        $fileNum = count($files) > 1 ? '_' . ($idx + 1) : '';
                        $model->attachments()->create([
                            'file_path' => $path,
                            'file_name' => 'Bukti_Transfer_' . $reference . $fileNum . '.' . $file->getClientOriginalExtension(),
                            'file_size' => $file->getSize(),
                            'mime_type' => $file->getClientMimeType(),
                        ]);
                    }
                }
            }

            // Record cash out transaction automatically for selected cash account
            $account = CashAccount::lockForUpdate()->findOrFail($cashAccountId);
            if ($amount > 0) {
                $recordCashTransaction->execute(
                    $account->id,
                    'out',
                    $category,
                    $amount,
                    "Pencairan {$model->request_number} via {$account->name} (Ref: {$reference})",
                    $user->id,
                    $model
                );
            }

            $recordHistory->execute($model, $oldStatus, RequestStatus::PAID->value, $user->id, "Pembayaran berhasil diproses via {$account->name} dengan No. Referensi Transfer: {$reference}");

            // Notify applicant
            $model->user?->notify(new \App\Notifications\PaymentProcessedNotification($type, $model->id, $model->request_number, $reference));

            return back()->with('success', "Pembayaran berhasil diproses via {$account->name} dan mutasi kas berhasil dicatat!");
        });
    }
}

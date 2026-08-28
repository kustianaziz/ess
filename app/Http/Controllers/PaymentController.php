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
                'klaim-lembur' => \App\Models\OvertimeClaim::findOrFail($id),
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
                'klaim-lembur' => (float)$model->amount,
                'perjalanan-dinas' => !empty($validated['allowance_breakdown']) 
                    ? (float)collect($validated['allowance_breakdown'])->sum('amount') 
                    : (float)($validated['disbursed_budget'] ?? $model->estimated_budget),
                default => 0,
            };

            $category = match($type) {
                'reimbursement' => 'reimburse',
                'operasional' => 'operasional_lain',
                'perjalanan-dinas' => 'perjalanan_dinas',
                'klaim-lembur' => 'lembur',
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

            $reqNum = $model->request_number ?? $model->claim_number;

            // Record cash out transaction automatically for selected cash account
            $account = CashAccount::lockForUpdate()->findOrFail($cashAccountId);
            if ($amount > 0) {
                $recordCashTransaction->execute(
                    $account->id,
                    'out',
                    $category,
                    $amount,
                    "Pencairan {$reqNum} via {$account->name} (Ref: {$reference})",
                    $user->id,
                    $model
                );
            }

            $recordHistory->execute($model, $oldStatus, RequestStatus::PAID->value, $user->id, "Pembayaran berhasil diproses via {$account->name} dengan No. Referensi Transfer: {$reference}");

            // Notify applicant
            $model->user?->notify(new \App\Notifications\PaymentProcessedNotification($type, $model->id, $reqNum, $reference));

            return back()->with('success', "Pembayaran berhasil diproses via {$account->name} dan mutasi kas berhasil dicatat!");
        });
    }

    public function cancel(
        Request $request,
        string $type,
        int $id,
        RecordStatusHistoryAction $recordHistory
    ): RedirectResponse {
        $user = $request->user();

        return DB::transaction(function() use ($type, $id, $user, $recordHistory) {
            $model = match($type) {
                'reimbursement' => ReimbursementRequest::findOrFail($id),
                'operasional' => OperationalRequest::findOrFail($id),
                'perjalanan-dinas' => BusinessTripRequest::findOrFail($id),
                'klaim-lembur' => \App\Models\OvertimeClaim::findOrFail($id),
                default => abort(404),
            };

            if ($model->status->value !== RequestStatus::PAID->value && $model->status->value !== RequestStatus::COMPLETED->value) {
                return back()->withErrors(['error' => 'Hanya pengajuan dengan status PAID yang dapat dibatalkan pembayarannya.']);
            }

            $oldStatus = $model->status->value;
            $reqNum = $model->request_number ?? $model->claim_number;

            // Find associated CashTransaction to reverse balance and delete it
            $transaction = \App\Models\CashTransaction::where('source_type', get_class($model))
                ->where('source_id', $model->id)
                ->first();

            $accountName = 'Kas';
            if ($transaction) {
                $cashAccount = CashAccount::lockForUpdate()->find($transaction->cash_account_id);
                if ($cashAccount) {
                    $cashAccount->increment('current_balance', $transaction->amount);
                    $accountName = $cashAccount->name;
                }
                $transaction->delete();
            }

            // Revert model status to APPROVED and clear payment details
            $updateData = [
                'status' => RequestStatus::APPROVED->value,
                'paid_at' => null,
                'paid_by' => null,
                'payment_reference' => null,
            ];

            if ($type === 'perjalanan-dinas') {
                $updateData['disbursed_budget'] = null;
                $updateData['allowance_breakdown'] = null;
            }

            $model->update($updateData);

            // Delete proof of payment attachments
            $attachments = $model->attachments()
                ->where('file_name', 'like', 'Bukti_Transfer_%')
                ->get();

            foreach ($attachments as $attachment) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
                }
                $attachment->delete();
            }

            // Record status history
            $recordHistory->execute(
                $model,
                $oldStatus,
                RequestStatus::APPROVED->value,
                $user->id,
                "Pembayaran dibatalkan oleh {$user->name}. Status dikembalikan ke Disetujui (Approved) dan saldo dikembalikan ke {$accountName}."
            );

            return back()->with('success', "Pembayaran untuk {$reqNum} berhasil dibatalkan. Saldo telah dikembalikan ke {$accountName}!");
        });
    }
}

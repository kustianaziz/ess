<?php

namespace App\Actions\CashOperational;

use App\Actions\Shared\GenerateRequestNumberAction;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RecordCashTransactionAction
{
    public function __construct(
        protected GenerateRequestNumberAction $generateRequestNumber
    ) {}

    public function execute(
        int $cashAccountId,
        string $type, // 'in' or 'out'
        string $category,
        float $amount,
        string $description,
        int $createdByUserId,
        ?Model $sourceModel = null,
        ?string $transactionDate = null
    ): CashTransaction {
        return DB::transaction(function () use (
            $cashAccountId,
            $type,
            $category,
            $amount,
            $description,
            $createdByUserId,
            $sourceModel,
            $transactionDate
        ) {
            $transactionNumber = $this->generateRequestNumber->execute('KAS', 'cash_transactions');

            $cashTransaction = CashTransaction::create([
                'transaction_number' => $transactionNumber,
                'cash_account_id' => $cashAccountId,
                'type' => $type,
                'category' => $category,
                'amount' => $amount,
                'description' => $description,
                'transaction_date' => $transactionDate ?? date('Y-m-d'),
                'source_type' => $sourceModel ? get_class($sourceModel) : null,
                'source_id' => $sourceModel ? $sourceModel->id : null,
                'created_by' => $createdByUserId,
                'status' => 'posted',
            ]);

            $cashAccount = CashAccount::lockForUpdate()->findOrFail($cashAccountId);

            if ($type === 'in') {
                $cashAccount->increment('current_balance', $amount);
            } else {
                $cashAccount->decrement('current_balance', $amount);
            }

            return $cashTransaction;
        });
    }
}

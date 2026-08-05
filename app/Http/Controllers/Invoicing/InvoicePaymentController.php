<?php

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\CashAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoicePaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'proof_of_payment' => 'nullable|array',
            'proof_of_payment.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        DB::transaction(function () use ($validated, $invoice, $request) {
            // Create InvoicePayment
            $payment = $invoice->payments()->create([
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'cash_account_id' => $validated['cash_account_id'],
            ]);

            // Update Invoice paid_amount and status
            $invoice->paid_amount += $validated['amount'];
            if ($invoice->paid_amount >= $invoice->total_amount) {
                $invoice->status = 'paid';
            } elseif ($invoice->paid_amount > 0) {
                $invoice->status = 'partial';
            }
            $invoice->save();

            // Update CashAccount balance
            $cashAccount = CashAccount::findOrFail($validated['cash_account_id']);
            $cashAccount->current_balance += $validated['amount'];
            $cashAccount->save();

            // Create CashTransaction
            $cashAccount->transactions()->create([
                'type' => 'in',
                'category' => 'lainnya',
                'amount' => $validated['amount'],
                'transaction_date' => $validated['payment_date'],
                'description' => 'Payment for Invoice #' . $invoice->id,
                'source_type' => InvoicePayment::class,
                'source_id' => $payment->id,
            ]);

            // Handle File Uploads
            if ($request->hasFile('proof_of_payment')) {
                foreach ($request->file('proof_of_payment') as $file) {
                    $path = $file->store('proofs_of_payment', 'public');
                    $payment->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }
}

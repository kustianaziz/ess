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
                'recorded_by' => \Illuminate\Support\Facades\Auth::id() ?? 1,
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
            $count = \App\Models\CashTransaction::whereYear('created_at', date('Y'))->whereMonth('created_at', date('m'))->count() + 1;
            $txNumber = 'KAS/' . date('Y/m/') . str_pad($count, 4, '0', STR_PAD_LEFT);
            $cashAccount->transactions()->create([
                'transaction_number' => $txNumber,
                'type' => 'in',
                'category' => 'lainnya',
                'amount' => $validated['amount'],
                'transaction_date' => $validated['payment_date'],
                'description' => 'Payment for Invoice #' . $invoice->invoice_number,
                'source_type' => InvoicePayment::class,
                'source_id' => $payment->id,
                'status' => 'posted',
                'created_by' => \Illuminate\Support\Facades\Auth::id() ?? 1,
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

            // Update Renewal Status if source is renewal
            if ($invoice->source_type === 'renewal' && $invoice->status === 'paid') {
                $renewal = \App\Models\RenewalRequest::find($invoice->source_id);
                if ($renewal && $renewal->status === 'invoiced_customer') {
                    $renewal->update(['status' => 'paid_customer']);
                }
            }

            // Create Journal Entry
            $kasCoa = \App\Models\Coa::where('code', '1.01.01.001')->first(); // Kas Operasional default
            $piutangCoa = \App\Models\Coa::where('code', '1.02.01')->first(); // Piutang Usaha
            
            if ($kasCoa && $piutangCoa) {
                app(\App\Actions\Accounting\RecordJournalAction::class)->execute(
                    $validated['payment_date'],
                    'Pelunasan Invoice ' . $invoice->invoice_number,
                    [
                        ['coa_id' => $kasCoa->id, 'debit' => $validated['amount'], 'credit' => 0],
                        ['coa_id' => $piutangCoa->id, 'debit' => 0, 'credit' => $validated['amount']],
                    ],
                    $payment
                );
            }
        });

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }
}

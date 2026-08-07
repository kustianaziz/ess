<?php

namespace App\Http\Controllers\Renewal;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\RenewalRequest;
use App\Models\VendorPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VendorPaymentController extends Controller
{
    public function store(Request $request, RenewalRequest $renewalRequest)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'notes' => 'nullable|string',
            'proof_of_payment' => 'nullable|array',
            'proof_of_payment.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        DB::transaction(function () use ($request, $renewalRequest) {
            // 1. Buat VendorPayment
            $vendorPayment = VendorPayment::create([
                'vendor_id' => $renewalRequest->domain->vendor_id,
                'renewal_request_id' => $renewalRequest->id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'paid_by' => Auth::id(),
            ]);

            // 2. Update RenewalRequest
            $renewalRequest->update([
                'vendor_payment_id' => $vendorPayment->id,
                'status' => 'paid_vendor',
            ]);

            // 3. Buat CashTransaction (pengeluaran ke vendor)
            $txNumber = app(\App\Actions\Shared\GenerateRequestNumberAction::class)->execute('KAS', 'cash_transactions', 'transaction_number');

            CashTransaction::create([
                'transaction_number' => $txNumber,
                'cash_account_id' => $request->cash_account_id,
                'type' => 'out',
                'category' => 'lainnya',
                'amount' => $request->amount,
                'description' => 'Bayar Vendor Renewal: ' . $renewalRequest->renewal_number . ' - ' . $renewalRequest->domain->name,
                'transaction_date' => $request->payment_date,
                'source_type' => VendorPayment::class,
                'source_id' => $vendorPayment->id,
                'created_by' => Auth::id(),
                'status' => 'posted',
            ]);

            // 4. Kurangi saldo kas
            CashAccount::where('id', $request->cash_account_id)
                ->decrement('current_balance', $request->amount);

            // 5. Multi-upload bukti pembayaran
            if ($request->hasFile('proof_of_payment')) {
                foreach ($request->file('proof_of_payment') as $file) {
                    $path = $file->store('attachments/vendor-payments', 'public');
                    Attachment::create([
                        'attachable_type' => VendorPayment::class,
                        'attachable_id' => $vendorPayment->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => Auth::id(),
                    ]);
                }
            }
            // 6. Buat Jurnal Akuntansi (Beban vs Kas)
            $bebanCoa = \App\Models\Coa::where('code', '5.01.01')->first(); // HPP - Pembelian
            $kasCoa = \App\Models\Coa::where('code', '1.01.01.001')->first();
            
            if ($bebanCoa && $kasCoa) {
                app(\App\Actions\Accounting\RecordJournalAction::class)->execute(
                    $request->payment_date,
                    'Bayar Vendor Renewal: ' . $renewalRequest->domain->name,
                    [
                        ['coa_id' => $bebanCoa->id, 'debit' => $request->amount, 'credit' => 0],
                        ['coa_id' => $kasCoa->id, 'debit' => 0, 'credit' => $request->amount],
                    ],
                    $vendorPayment
                );
            }
        });

        return redirect()->back()->with('success', 'Pembayaran vendor berhasil dicatat dan saldo kas telah dikurangi.');
    }
}

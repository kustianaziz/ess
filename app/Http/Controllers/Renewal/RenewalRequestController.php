<?php

namespace App\Http\Controllers\Renewal;

use App\Http\Controllers\Controller;
use App\Models\CashAccount;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\RenewalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RenewalRequestController extends Controller
{
    public function index()
    {
        $renewals = RenewalRequest::with(['domain.customer', 'domain.vendor', 'invoice', 'vendorPayment'])
            ->latest()->get();

        return Inertia::render('Renewal/Renewals/Index', [
            'renewals' => $renewals,
            'domains' => Domain::with('customer')->where('status', '!=', 'cancelled')->get(),
        ]);
    }

    public function store(Request $request, \App\Actions\Shared\GenerateRequestNumberAction $generateRequestNumber)
    {
        $validated = $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'period_year' => 'required|integer|min:1|max:10',
            'notes' => 'nullable|string',
        ]);

        $renewalNumber = $generateRequestNumber->execute('RN', 'renewal_requests', 'renewal_number');

        $domain = Domain::findOrFail($validated['domain_id']);

        RenewalRequest::create([
            'renewal_number' => $renewalNumber,
            'domain_id' => $validated['domain_id'],
            'period_year' => $validated['period_year'],
            'old_expired_date' => $domain->expired_date,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Renewal Request berhasil dibuat.');
    }

    public function show(RenewalRequest $renewalRequest)
    {
        $renewalRequest->load(['domain.customer', 'domain.vendor', 'invoice.items', 'invoice.payments.attachments', 'vendorPayment.attachments']);

        return Inertia::render('Renewal/Renewals/Show', [
            'renewal' => $renewalRequest,
            'cashAccounts' => CashAccount::where('is_active', true)->get(),
        ]);
    }

    public function generateInvoice(Request $request, RenewalRequest $renewalRequest, \App\Actions\Shared\GenerateRequestNumberAction $generateRequestNumber)
    {
        $validated = $request->validate([
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $renewalRequest, $generateRequestNumber) {
            $invoiceNumber = $generateRequestNumber->execute('INV', 'invoices', 'invoice_number');

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $renewalRequest->domain->customer_id,
                'source_type' => 'renewal',
                'source_id' => $renewalRequest->id,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'subtotal' => $validated['subtotal'],
                'tax_amount' => $validated['tax_amount'],
                'total_amount' => $validated['total_amount'],
                'paid_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['qty'] * $item['unit_price'],
                ]);
            }

            $renewalRequest->update([
                'invoice_id' => $invoice->id,
                'status' => 'invoiced_customer',
            ]);
        });

        return redirect()->back()->with('success', 'Invoice berhasil dibuat dan dihubungkan ke renewal ini.');
    }

    public function markPaidCustomer(Request $request, RenewalRequest $renewalRequest)
    {
        $validated = $request->validate([
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'payment_date' => 'required|date',
        ]);

        \DB::transaction(function () use ($validated, $renewalRequest) {
            $invoice = $renewalRequest->invoice;
            
            if ($invoice) {
                // Buat Payment untuk Invoice tersebut
                $payment = $invoice->payments()->create([
                    'amount' => $invoice->total_amount,
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => 'transfer',
                    'recorded_by' => \Illuminate\Support\Facades\Auth::id() ?? 1,
                ]);

                // Update Invoice
                $invoice->paid_amount = $invoice->total_amount;
                $invoice->status = 'paid';
                $invoice->save();

                // Update CashAccount balance
                $cashAccount = CashAccount::findOrFail($validated['cash_account_id']);
                $cashAccount->current_balance += $invoice->total_amount;
                $cashAccount->save();

                // Create CashTransaction
                $txNumber = app(\App\Actions\Shared\GenerateRequestNumberAction::class)->execute('KAS', 'cash_transactions', 'transaction_number');

                $cashAccount->transactions()->create([
                    'transaction_number' => $txNumber,
                    'type' => 'in',
                    'category' => 'lainnya',
                    'amount' => $invoice->total_amount,
                    'transaction_date' => $validated['payment_date'],
                    'description' => 'Payment for Invoice #' . $invoice->invoice_number . ' (Renewal)',
                    'source_type' => \App\Models\InvoicePayment::class,
                    'source_id' => $payment->id,
                    'status' => 'posted',
                    'created_by' => \Illuminate\Support\Facades\Auth::id() ?? 1,
                ]);

                // Jurnal Pelunasan Piutang
                $kasCoa = \App\Models\Coa::where('code', '1.01.01.001')->first();
                $piutangCoa = \App\Models\Coa::where('code', '1.02.01')->first();
                
                if ($kasCoa && $piutangCoa) {
                    app(\App\Actions\Accounting\RecordJournalAction::class)->execute(
                        $validated['payment_date'],
                        'Pelunasan Invoice Renewal ' . $invoice->invoice_number,
                        [
                            ['coa_id' => $kasCoa->id, 'debit' => $invoice->total_amount, 'credit' => 0],
                            ['coa_id' => $piutangCoa->id, 'debit' => 0, 'credit' => $invoice->total_amount],
                        ],
                        $payment
                    );
                }
            }

            $renewalRequest->update(['status' => 'paid_customer']);
        });

        return redirect()->back()->with('success', 'Pembayaran dari klien telah dicatat dan masuk ke kas.');
    }

    public function complete(Request $request, RenewalRequest $renewalRequest)
    {
        $validated = $request->validate([
            'new_expired_date' => 'required|date|after:today',
        ]);

        DB::transaction(function () use ($validated, $renewalRequest) {
            $renewalRequest->domain->update([
                'expired_date' => $validated['new_expired_date'],
                'status' => 'active',
            ]);

            $renewalRequest->update([
                'new_expired_date' => $validated['new_expired_date'],
                'status' => 'completed',
                'processed_by' => Auth::id(),
            ]);
        });

        return redirect()->back()->with('success', 'Renewal selesai! Tanggal expired domain telah diperbarui.');
    }

    public function undoComplete(RenewalRequest $renewalRequest)
    {
        if ($renewalRequest->status !== 'completed') return back()->with('error', 'Status tidak valid.');
        
        DB::transaction(function () use ($renewalRequest) {
            $renewalRequest->domain->update([
                'expired_date' => $renewalRequest->old_expired_date,
                // Status domain dibiarkan atau diupdate jika perlu
            ]);
            $renewalRequest->update([
                'status' => 'paid_vendor',
                'new_expired_date' => null,
            ]);
        });
        return back()->with('success', 'Penyelesaian renewal dibatalkan.');
    }

    public function undoPaidVendor(RenewalRequest $renewalRequest)
    {
        if ($renewalRequest->status !== 'paid_vendor') return back()->with('error', 'Status tidak valid.');
        $vendorPayment = $renewalRequest->vendorPayment;
        if (!$vendorPayment) return back()->with('error', 'Pembayaran vendor tidak ditemukan.');

        $isJournalled = \App\Models\JournalEntry::where('reference_type', \App\Models\VendorPayment::class)
            ->where('reference_id', $vendorPayment->id)->where('status', '!=', 'void')->exists();
        if ($isJournalled) return back()->with('error', 'Gagal: Pembayaran ini sudah dibukukan di jurnal.');

        DB::transaction(function () use ($renewalRequest, $vendorPayment) {
            $cashTx = \App\Models\CashTransaction::where('source_type', \App\Models\VendorPayment::class)
                ->where('source_id', $vendorPayment->id)->first();
            if ($cashTx) {
                $cashAcc = \App\Models\CashAccount::find($cashTx->cash_account_id);
                if ($cashAcc) {
                    $cashAcc->current_balance += $cashTx->amount; // kembalikan uang
                    $cashAcc->save();
                }
                $cashTx->delete();
            }
            $vendorPayment->delete();
            $renewalRequest->update([
                'status' => 'paid_customer',
                'vendor_payment_id' => null,
            ]);
        });
        return back()->with('success', 'Pembayaran vendor dibatalkan.');
    }

    public function undoPaidCustomer(RenewalRequest $renewalRequest)
    {
        if ($renewalRequest->status !== 'paid_customer') return back()->with('error', 'Status tidak valid.');
        $invoice = $renewalRequest->invoice;
        if (!$invoice || $invoice->payments->count() === 0) return back()->with('error', 'Invoice/Pembayaran tidak ditemukan.');
        
        $payment = $invoice->payments->first(); // asumsikan 1 pembayaran penuh
        $isJournalled = \App\Models\JournalEntry::where('reference_type', \App\Models\InvoicePayment::class)
            ->where('reference_id', $payment->id)->where('status', '!=', 'void')->exists();
        if ($isJournalled) return back()->with('error', 'Gagal: Pembayaran klien ini sudah dibukukan di jurnal.');

        DB::transaction(function () use ($renewalRequest, $invoice, $payment) {
            $cashTx = \App\Models\CashTransaction::where('source_type', \App\Models\InvoicePayment::class)
                ->where('source_id', $payment->id)->first();
            if ($cashTx) {
                $cashAcc = \App\Models\CashAccount::find($cashTx->cash_account_id);
                if ($cashAcc) {
                    $cashAcc->current_balance -= $cashTx->amount; // tarik uang kembali
                    $cashAcc->save();
                }
                $cashTx->delete();
            }
            $payment->delete();
            
            $invoice->update([
                'status' => 'sent',
                'paid_amount' => 0
            ]);

            $renewalRequest->update(['status' => 'invoiced_customer']);
        });
        return back()->with('success', 'Pembayaran klien dibatalkan.');
    }

    public function undoInvoice(RenewalRequest $renewalRequest)
    {
        if ($renewalRequest->status !== 'invoiced_customer') return back()->with('error', 'Status tidak valid.');
        $invoice = $renewalRequest->invoice;
        if (!$invoice) return back()->with('error', 'Invoice tidak ditemukan.');

        $isJournalled = \App\Models\JournalEntry::where('reference_type', \App\Models\Invoice::class)
            ->where('reference_id', $invoice->id)->where('status', '!=', 'void')->exists();
        if ($isJournalled) return back()->with('error', 'Gagal: Invoice ini sudah dibukukan di jurnal (Piutang).');

        DB::transaction(function () use ($renewalRequest, $invoice) {
            $invoice->items()->delete();
            $invoice->delete();
            $renewalRequest->update([
                'status' => 'pending',
                'invoice_id' => null,
            ]);
        });
        return back()->with('success', 'Invoice dibatalkan dan dihapus.');
    }
}

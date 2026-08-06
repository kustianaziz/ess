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
        $renewals = RenewalRequest::with(['domain.customer', 'domain.vendor', 'invoice'])
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

    public function markPaidCustomer(RenewalRequest $renewalRequest)
    {
        $renewalRequest->update(['status' => 'paid_customer']);
        return redirect()->back()->with('success', 'Status diperbarui: Pelanggan sudah membayar.');
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
}

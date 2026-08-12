<?php

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\CashAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('customer')->latest();

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->date_start) {
            $query->whereDate('invoice_date', '>=', $request->date_start);
        }
        if ($request->date_end) {
            $query->whereDate('invoice_date', '<=', $request->date_end);
        }
        if ($request->due_start) {
            $query->whereDate('due_date', '>=', $request->due_start);
        }
        if ($request->due_end) {
            $query->whereDate('due_date', '<=', $request->due_end);
        }

        $invoices = $query->get();
        
        $upcoming_renewals = Invoice::with('customer')
            ->whereDate('due_date', '<=', now()->addDays(30))
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('invoices')->groupBy('customer_id');
            })
            ->get();
        
        return Inertia::render('Invoicing/Invoices/Index', [
            'invoices' => $invoices,
            'upcoming_renewals' => $upcoming_renewals,
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['customer_id', 'date_start', 'date_end', 'due_start', 'due_end'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Invoicing/Invoices/Create', [
            'customers' => Customer::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'po_number' => 'nullable|string',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $invoice = Invoice::create([
                'invoice_number' => $validated['invoice_number'],
                'customer_id' => $validated['customer_id'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'po_number' => $validated['po_number'] ?? null,
                'subtotal' => $validated['subtotal'],
                'tax_amount' => $validated['tax_amount'],
                'total_amount' => $validated['total_amount'],
                'notes' => $validated['notes'],
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['qty'] * $item['unit_price'],
                ]);
            }
        });

        return redirect()->route('invoicing.invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'items', 'payments']);
        
        return Inertia::render('Invoicing/Invoices/Show', [
            'invoice' => $invoice,
            'cashAccounts' => CashAccount::where('is_active', true)->get()
        ]);
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load(['customer', 'items']);
        return Inertia::render('Invoicing/Invoices/Edit', [
            'invoice' => $invoice,
            'customers' => Customer::latest()->get()
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return redirect()->back()->withErrors(['error' => 'Hanya invoice dengan status Draft yang bisa diedit.']);
        }

        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:invoices,invoice_number,' . $invoice->id,
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'po_number' => 'nullable|string',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $invoice) {
            $invoice->update([
                'invoice_number' => $validated['invoice_number'],
                'customer_id' => $validated['customer_id'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'po_number' => $validated['po_number'] ?? null,
                'subtotal' => $validated['subtotal'],
                'tax_amount' => $validated['tax_amount'],
                'total_amount' => $validated['total_amount'],
                'notes' => $validated['notes'],
            ]);

            // Hapus items lama
            $invoice->items()->delete();

            // Masukkan items baru
            foreach ($validated['items'] as $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['qty'] * $item['unit_price'],
                ]);
            }
        });

        return redirect()->route('invoicing.invoices.show', $invoice->id)->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoicing.invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function markAsSent(Invoice $invoice)
    {
        \DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => 'sent']);
        });

        return redirect()->back()->with('success', 'Invoice telah di-posting.');
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['customer.service', 'items', 'payments']);
        
        $terbilang = $this->terbilang($invoice->total_amount);
        $terbilang = ucwords(strtolower($terbilang));
        
        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'terbilang'));
        
        return $pdf->download('Invoice_' . str_replace('/', '_', $invoice->invoice_number) . '.pdf');
    }

    public function duplicate(Invoice $invoice)
    {
        $invoice->load('items');

        \DB::transaction(function () use ($invoice) {
            $invNumber = app(\App\Actions\Shared\GenerateRequestNumberAction::class)->execute('INV', 'invoices', 'invoice_number');

            // Perpanjang 1 bulan
            $newInvoiceDate = \Carbon\Carbon::parse($invoice->due_date);
            $newDueDate = $newInvoiceDate->copy()->addMonths(1);

            $newInvoice = Invoice::create([
                'invoice_number' => $invNumber,
                'customer_id' => $invoice->customer_id,
                'source_type' => $invoice->source_type,
                'source_id' => $invoice->source_id,
                'invoice_date' => $newInvoiceDate->format('Y-m-d'),
                'due_date' => $newDueDate->format('Y-m-d'),
                'subtotal' => $invoice->subtotal,
                'tax_amount' => $invoice->tax_amount,
                'total_amount' => $invoice->total_amount,
                'notes' => $invoice->notes,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($invoice->items as $item) {
                \App\Models\InvoiceItem::create([
                    'invoice_id' => $newInvoice->id,
                    'description' => $item->description,
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]);
            }
        });

        return back()->with('success', 'Invoice baru berhasil di-generate sebagai Draft.');
    }

    private function terbilang($x) {
        $x = abs($x);
        $angka = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
        $temp = "";
        if ($x < 12) {
            $temp = " " . $angka[$x];
        } else if ($x < 20) {
            $temp = $this->terbilang($x - 10) . " belas";
        } else if ($x < 100) {
            $temp = $this->terbilang($x / 10) . " puluh" . $this->terbilang($x % 10);
        } else if ($x < 200) {
            $temp = " seratus" . $this->terbilang($x - 100);
        } else if ($x < 1000) {
            $temp = $this->terbilang($x / 100) . " ratus" . $this->terbilang($x % 100);
        } else if ($x < 2000) {
            $temp = " seribu" . $this->terbilang($x - 1000);
        } else if ($x < 1000000) {
            $temp = $this->terbilang($x / 1000) . " ribu" . $this->terbilang($x % 1000);
        } else if ($x < 1000000000) {
            $temp = $this->terbilang($x / 1000000) . " juta" . $this->terbilang($x % 1000000);
        } else if ($x < 1000000000000) {
            $temp = $this->terbilang($x / 1000000000) . " milyar" . $this->terbilang(fmod($x, 1000000000));
        } else if ($x < 1000000000000000) {
            $temp = $this->terbilang($x / 1000000000000) . " trilyun" . $this->terbilang(fmod($x, 1000000000000));
        }
        return $temp;
    }
}

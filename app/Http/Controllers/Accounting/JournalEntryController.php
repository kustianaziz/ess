<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Coa;
use App\Models\CashTransaction;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\VendorPayment;
use App\Models\AccountingPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class JournalEntryController extends Controller
{
    private function generateJournalNumber(string $date): string
    {
        $datePrefix = date('Ymd', strtotime($date));
        $lastJournal = JournalEntry::where('journal_number', 'like', "JV-{$datePrefix}-%")
            ->orderBy('journal_number', 'desc')
            ->first();
        $number = $lastJournal ? intval(substr($lastJournal->journal_number, -3)) + 1 : 1;
        return "JV-{$datePrefix}-" . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        // Build journal query with filters
        $journalsQuery = JournalEntry::with(['items.coa', 'creator'])->orderBy('date', 'desc')->orderBy('id', 'desc');

        if ($request->filled('date_from')) {
            $journalsQuery->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $journalsQuery->whereDate('date', '<=', $request->date_to);
        }
        if ($request->filled('period_id')) {
            $period = AccountingPeriod::find($request->period_id);
            if ($period) {
                $journalsQuery->whereDate('date', '>=', $period->start_date)->whereDate('date', '<=', $period->end_date);
            }
        }

        $journals = $journalsQuery->get();

        // Collect all source transactions
        $allJournalRefs = JournalEntry::whereNotNull('reference_type')->where('status', '!=', 'void')->get()->keyBy(function ($j) {
            return $j->reference_type . '_' . $j->reference_id;
        });

        $transactions = collect();

        // Cash Transactions
        $cashTransactions = CashTransaction::where('status', 'posted')->get();
        foreach ($cashTransactions as $ct) {
            $refKey = CashTransaction::class . '_' . $ct->id;
            $isJournalled = $allJournalRefs->has($refKey);
            $transactions->push([
                'id'               => 'ct_' . $ct->id,
                'source_type'      => CashTransaction::class,
                'source_label'     => 'Kas Operasional',
                'source_id'        => $ct->id,
                'date'             => $ct->transaction_date,
                'description'      => ($ct->type == 'in' ? '[KAS MASUK]' : '[KAS KELUAR]') . ' ' . $ct->description,
                'amount'           => $ct->amount,
                'reference_number' => $ct->transaction_number,
                'is_journalled'    => $isJournalled,
                'type'             => $ct->type,
            ]);
        }

        // Invoices (created/sent - as Piutang)
        $invoices = Invoice::whereIn('status', ['sent', 'partial', 'paid', 'overdue'])->with('customer')->get();
        foreach ($invoices as $inv) {
            $refKey = Invoice::class . '_' . $inv->id;
            $isJournalled = $allJournalRefs->has($refKey);
            $transactions->push([
                'id'               => 'inv_' . $inv->id,
                'source_type'      => Invoice::class,
                'source_label'     => 'Penerbitan Invoice',
                'source_id'        => $inv->id,
                'date'             => $inv->invoice_date,
                'description'      => '[PIUTANG] Invoice ' . $inv->invoice_number . ' - ' . ($inv->customer->name ?? ''),
                'amount'           => $inv->total_amount,
                'reference_number' => $inv->invoice_number,
                'is_journalled'    => $isJournalled,
                'type'             => 'in',
            ]);
        }

        // Invoice Payments (Cash receipt)
        $invoicePayments = InvoicePayment::with('invoice')->get();
        foreach ($invoicePayments as $ip) {
            $refKey = InvoicePayment::class . '_' . $ip->id;
            $isJournalled = $allJournalRefs->has($refKey);
            $transactions->push([
                'id'               => 'ip_' . $ip->id,
                'source_type'      => InvoicePayment::class,
                'source_label'     => 'Pembayaran Invoice',
                'source_id'        => $ip->id,
                'date'             => $ip->payment_date,
                'description'      => '[PENERIMAAN KAS] ' . ($ip->invoice->invoice_number ?? '-'),
                'amount'           => $ip->amount,
                'reference_number' => 'INV-PAY-' . str_pad($ip->id, 4, '0', STR_PAD_LEFT),
                'is_journalled'    => $isJournalled,
                'type'             => 'in',
            ]);
        }

        // Vendor Payments
        $vendorPayments = VendorPayment::with('vendor')->get();
        foreach ($vendorPayments as $vp) {
            $refKey = VendorPayment::class . '_' . $vp->id;
            $isJournalled = $allJournalRefs->has($refKey);
            $transactions->push([
                'id'               => 'vp_' . $vp->id,
                'source_type'      => VendorPayment::class,
                'source_label'     => 'Pembayaran Vendor',
                'source_id'        => $vp->id,
                'date'             => $vp->payment_date,
                'description'      => '[PEMBAYARAN VENDOR] ' . ($vp->vendor->name ?? '-'),
                'amount'           => $vp->amount,
                'reference_number' => 'VEND-PAY-' . str_pad($vp->id, 4, '0', STR_PAD_LEFT),
                'is_journalled'    => $isJournalled,
                'type'             => 'out',
            ]);
        }

        // Apply filters on pending transactions
        $pendingTransactions = $transactions->where('is_journalled', false);

        if ($request->filled('source_type')) {
            $pendingTransactions = $pendingTransactions->where('source_type', $request->source_type);
        }
        if ($request->filled('date_from')) {
            $pendingTransactions = $pendingTransactions->filter(fn($t) => $t['date'] >= $request->date_from);
        }
        if ($request->filled('date_to')) {
            $pendingTransactions = $pendingTransactions->filter(fn($t) => $t['date'] <= $request->date_to);
        }

        $pendingTransactions = $pendingTransactions->sortByDesc('date')->values();

        return Inertia::render('Accounting/Journals/Index', [
            'journals'            => $journals,
            'pendingTransactions' => $pendingTransactions,
            'coas'                => Coa::where('is_header', false)->orderBy('code')->get(),
            'periods'             => AccountingPeriod::orderBy('start_date', 'desc')->get(),
            'filters'             => $request->only(['date_from', 'date_to', 'period_id', 'source_type']),
            'sourceTypes'         => [
                ['value' => CashTransaction::class, 'label' => 'Kas Operasional'],
                ['value' => Invoice::class, 'label' => 'Penerbitan Invoice'],
                ['value' => InvoicePayment::class, 'label' => 'Pembayaran Invoice'],
                ['value' => VendorPayment::class, 'label' => 'Pembayaran Vendor'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        // Mode: "Journal from source transaction" with split items
        if ($request->has('source_type')) {
            $request->validate([
                'source_type'      => 'required|string',
                'source_id'        => 'required|integer',
                'date'             => 'required|date',
                'description'      => 'required|string',
                'items'            => 'required|array|min:2',
                'items.*.coa_id'   => 'required|exists:coas,id',
                'items.*.debit'    => 'required|numeric|min:0',
                'items.*.credit'   => 'required|numeric|min:0',
            ]);

            $totalDebit  = collect($request->items)->sum('debit');
            $totalCredit = collect($request->items)->sum('credit');

            if (round($totalDebit, 2) != round($totalCredit, 2)) {
                return back()->withErrors(['items' => 'Total Debit harus sama dengan Total Kredit.']);
            }
            if ($totalDebit == 0) {
                return back()->withErrors(['items' => 'Nominal tidak boleh nol.']);
            }

            DB::transaction(function () use ($request) {
                $journal = JournalEntry::create([
                    'journal_number' => $this->generateJournalNumber($request->date),
                    'date'           => $request->date,
                    'description'    => $request->description,
                    'status'         => 'posted',
                    'created_by'     => auth()->id(),
                    'reference_type' => $request->source_type,
                    'reference_id'   => $request->source_id,
                ]);
                foreach ($request->items as $item) {
                    $journal->items()->create([
                        'coa_id'      => $item['coa_id'],
                        'description' => $item['description'] ?? $request->description,
                        'debit'       => $item['debit'],
                        'credit'      => $item['credit'],
                    ]);
                }
            });

            return back()->with('success', 'Transaksi berhasil dijurnal.');
        }

        // Mode: Manual journal entry
        $request->validate([
            'date'             => 'required|date',
            'description'      => 'required|string',
            'items'            => 'required|array|min:2',
            'items.*.coa_id'   => 'required|exists:coas,id',
            'items.*.debit'    => 'required|numeric|min:0',
            'items.*.credit'   => 'required|numeric|min:0',
        ]);

        $totalDebit  = collect($request->items)->sum('debit');
        $totalCredit = collect($request->items)->sum('credit');

        if (round($totalDebit, 2) != round($totalCredit, 2)) {
            return back()->withErrors(['items' => 'Total Debit harus sama dengan Total Kredit.']);
        }
        if ($totalDebit == 0) {
            return back()->withErrors(['items' => 'Total nominal tidak boleh nol.']);
        }

        DB::transaction(function () use ($request) {
            $journal = JournalEntry::create([
                'journal_number' => $this->generateJournalNumber($request->date),
                'date'           => $request->date,
                'description'    => $request->description,
                'status'         => 'posted',
                'created_by'     => auth()->id(),
            ]);
            foreach ($request->items as $item) {
                $journal->items()->create([
                    'coa_id'      => $item['coa_id'],
                    'description' => $item['description'] ?? $request->description,
                    'debit'       => $item['debit'],
                    'credit'      => $item['credit'],
                ]);
            }
        });

        return back()->with('success', 'Jurnal manual berhasil disimpan.');
    }

    public function void(JournalEntry $journal)
    {
        if ($journal->status === 'void') {
            return back()->withErrors(['message' => 'Jurnal ini sudah dibatalkan.']);
        }
        $journal->update(['status' => 'void']);
        return back()->with('success', 'Jurnal ' . $journal->journal_number . ' berhasil dibatalkan (void).');
    }

    public function show($id)
    {
        $journal = JournalEntry::with(['items.coa', 'creator', 'poster'])->findOrFail($id);
        return Inertia::render('Accounting/Journals/Show', [
            'journal' => $journal
        ]);
    }
}

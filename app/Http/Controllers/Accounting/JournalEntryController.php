<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Coa;
use App\Models\CashTransaction;
use App\Models\InvoicePayment;
use App\Models\VendorPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class JournalEntryController extends Controller
{
    public function index()
    {
        $journals = JournalEntry::with(['items.coa', 'creator'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $cashTransactions = CashTransaction::where('status', 'posted')->get();
        $invoicePayments = InvoicePayment::with('invoice')->get();
        $vendorPayments = VendorPayment::with('vendor')->get();

        $transactions = collect();
        $journalRefs = $journals->whereNotNull('reference_type')->keyBy(function($j) {
            return $j->reference_type . '_' . $j->reference_id;
        });

        foreach($cashTransactions as $ct) {
            $refKey = CashTransaction::class . '_' . $ct->id;
            $isJournalled = $journalRefs->has($refKey);
            $transactions->push([
                'id' => 'ct_'.$ct->id,
                'source_type' => CashTransaction::class,
                'source_id' => $ct->id,
                'date' => $ct->transaction_date,
                'description' => 'Kas ' . ($ct->type == 'in' ? 'Masuk' : 'Keluar') . ': ' . $ct->description,
                'amount' => $ct->amount,
                'reference_number' => $ct->transaction_number,
                'is_journalled' => $isJournalled,
                'journal' => $isJournalled ? $journalRefs->get($refKey) : null,
                'type' => $ct->type // 'in' or 'out'
            ]);
        }

        foreach($invoicePayments as $ip) {
            $refKey = InvoicePayment::class . '_' . $ip->id;
            $isJournalled = $journalRefs->has($refKey);
            $transactions->push([
                'id' => 'ip_'.$ip->id,
                'source_type' => InvoicePayment::class,
                'source_id' => $ip->id,
                'date' => $ip->payment_date,
                'description' => 'Pembayaran Invoice: ' . ($ip->invoice->invoice_number ?? ''),
                'amount' => $ip->amount,
                'reference_number' => 'INV-PAY-' . $ip->id,
                'is_journalled' => $isJournalled,
                'journal' => $isJournalled ? $journalRefs->get($refKey) : null,
                'type' => 'in' 
            ]);
        }

        foreach($vendorPayments as $vp) {
            $refKey = VendorPayment::class . '_' . $vp->id;
            $isJournalled = $journalRefs->has($refKey);
            $transactions->push([
                'id' => 'vp_'.$vp->id,
                'source_type' => VendorPayment::class,
                'source_id' => $vp->id,
                'date' => $vp->payment_date,
                'description' => 'Pembayaran Vendor: ' . ($vp->vendor->name ?? ''),
                'amount' => $vp->amount,
                'reference_number' => 'VEND-PAY-' . $vp->id,
                'is_journalled' => $isJournalled,
                'journal' => $isJournalled ? $journalRefs->get($refKey) : null,
                'type' => 'out'
            ]);
        }
        
        $pendingTransactions = $transactions->where('is_journalled', false)->sortByDesc('date')->values();

        return Inertia::render('Accounting/Journals/Index', [
            'journals' => $journals,
            'pendingTransactions' => $pendingTransactions,
            'coas' => Coa::where('is_header', false)->orderBy('code')->get()
        ]);
    }

    public function store(Request $request)
    {
        if ($request->has('source_type')) {
            $request->validate([
                'source_type' => 'required|string',
                'source_id' => 'required|integer',
                'debit_coa_id' => 'required|exists:coas,id',
                'credit_coa_id' => 'required|exists:coas,id',
                'amount' => 'required|numeric|min:0.01',
                'date' => 'required|date',
                'description' => 'required|string',
            ]);

            DB::transaction(function () use ($request) {
                $datePrefix = date('Ymd', strtotime($request->date));
                $lastJournal = JournalEntry::where('journal_number', 'like', "JV-{$datePrefix}-%")
                    ->orderBy('journal_number', 'desc')
                    ->first();
                
                $number = 1;
                if ($lastJournal) {
                    $lastNumber = intval(substr($lastJournal->journal_number, -3));
                    $number = $lastNumber + 1;
                }
                $journalNumber = "JV-{$datePrefix}-" . str_pad($number, 3, '0', STR_PAD_LEFT);

                $journal = JournalEntry::create([
                    'journal_number' => $journalNumber,
                    'date' => $request->date,
                    'description' => $request->description,
                    'status' => 'posted',
                    'created_by' => auth()->id(),
                    'reference_type' => $request->source_type,
                    'reference_id' => $request->source_id,
                ]);

                $journal->items()->create([
                    'coa_id' => $request->debit_coa_id,
                    'description' => $request->description,
                    'debit' => $request->amount,
                    'credit' => 0,
                ]);

                $journal->items()->create([
                    'coa_id' => $request->credit_coa_id,
                    'description' => $request->description,
                    'debit' => 0,
                    'credit' => $request->amount,
                ]);
            });

            return back()->with('success', 'Transaksi berhasil dijurnal.');
        }

        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'items' => 'required|array|min:2',
            'items.*.coa_id' => 'required|exists:coas,id',
            'items.*.debit' => 'required|numeric|min:0',
            'items.*.credit' => 'required|numeric|min:0',
        ]);

        $totalDebit = collect($request->items)->sum('debit');
        $totalCredit = collect($request->items)->sum('credit');

        if ($totalDebit != $totalCredit) {
            return back()->withErrors(['items' => 'Total Debit must equal Total Credit.']);
        }

        if ($totalDebit == 0) {
            return back()->withErrors(['items' => 'Total amount must be greater than 0.']);
        }

        DB::transaction(function () use ($request) {
            $datePrefix = date('Ymd', strtotime($request->date));
            $lastJournal = JournalEntry::where('journal_number', 'like', "JV-{$datePrefix}-%")
                ->orderBy('journal_number', 'desc')
                ->first();
            
            $number = 1;
            if ($lastJournal) {
                $lastNumber = intval(substr($lastJournal->journal_number, -3));
                $number = $lastNumber + 1;
            }
            $journalNumber = "JV-{$datePrefix}-" . str_pad($number, 3, '0', STR_PAD_LEFT);

            $journal = JournalEntry::create([
                'journal_number' => $journalNumber,
                'date' => $request->date,
                'description' => $request->description,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $journal->items()->create([
                    'coa_id' => $item['coa_id'],
                    'description' => $item['description'] ?? $request->description,
                    'debit' => $item['debit'],
                    'credit' => $item['credit'],
                ]);
            }
        });

        return back()->with('success', 'Journal entry created successfully.');
    }

    public function show($id)
    {
        $journal = JournalEntry::with(['items.coa', 'creator', 'poster'])->findOrFail($id);

        return Inertia::render('Accounting/Journals/Show', [
            'journal' => $journal
        ]);
    }
}

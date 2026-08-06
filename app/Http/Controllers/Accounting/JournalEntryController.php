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
use App\Models\ReimbursementRequest;
use App\Models\OperationalRequest;
use App\Models\BusinessTripSettlement;
use App\Models\MonthlyBillPayment;
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
        $periods = AccountingPeriod::orderBy('start_date', 'desc')->get();

        // Default: active period
        $activePeriod = $periods->where('is_closed', false)->first();
        $defaultPeriodId = $activePeriod?->id;
        $defaultDateFrom = $activePeriod?->start_date?->format('Y-m-d');
        $defaultDateTo   = $activePeriod?->end_date?->format('Y-m-d');

        $periodId   = $request->input('period_id', $defaultPeriodId);
        $dateFrom   = $request->input('date_from', $defaultDateFrom);
        $dateTo     = $request->input('date_to', $defaultDateTo);
        $sourceType = $request->input('source_type', '');

        // When period changes, override date range from period dates
        if ($request->filled('period_id') && !$request->filled('date_from')) {
            $selectedPeriod = $periods->firstWhere('id', $periodId);
            if ($selectedPeriod) {
                $dateFrom = $selectedPeriod->start_date->format('Y-m-d');
                $dateTo   = $selectedPeriod->end_date->format('Y-m-d');
            }
        }

        // Build journal query
        $journalsQuery = JournalEntry::with(['items.coa', 'creator'])->orderBy('date', 'desc')->orderBy('id', 'desc');
        if ($dateFrom) $journalsQuery->whereDate('date', '>=', $dateFrom);
        if ($dateTo)   $journalsQuery->whereDate('date', '<=', $dateTo);
        $journals = $journalsQuery->get();

        // Build ref map (exclude voided)
        $allJournalRefs = JournalEntry::whereNotNull('reference_type')->where('status', '!=', 'void')->get()->keyBy(function ($j) {
            return $j->reference_type . '_' . $j->reference_id;
        });

        $transactions = collect();

        // 1. Kas Operasional (Cash Transactions)
        foreach (CashTransaction::where('status', 'posted')->get() as $ct) {
            $refKey = CashTransaction::class . '_' . $ct->id;
            $transactions->push([
                'id'               => 'ct_' . $ct->id,
                'source_type'      => CashTransaction::class,
                'source_label'     => 'Kas Operasional',
                'source_id'        => $ct->id,
                'date'             => $ct->transaction_date,
                'description'      => ($ct->type == 'in' ? '[KAS MASUK] ' : '[KAS KELUAR] ') . $ct->description,
                'amount'           => $ct->amount,
                'reference_number' => $ct->transaction_number ?? 'KAS-' . $ct->id,
                'is_journalled'    => $allJournalRefs->has($refKey),
                'type'             => $ct->type,
            ]);
        }

        // 2. Penerbitan Invoice (Piutang)
        foreach (Invoice::whereIn('status', ['sent', 'partial', 'paid', 'overdue'])->with('customer')->get() as $inv) {
            $refKey = Invoice::class . '_' . $inv->id;
            $transactions->push([
                'id'               => 'inv_' . $inv->id,
                'source_type'      => Invoice::class,
                'source_label'     => 'Invoice / Piutang',
                'source_id'        => $inv->id,
                'date'             => $inv->invoice_date,
                'description'      => '[PIUTANG] ' . $inv->invoice_number . ' - ' . ($inv->customer->name ?? ''),
                'amount'           => $inv->total_amount,
                'reference_number' => $inv->invoice_number,
                'is_journalled'    => $allJournalRefs->has($refKey),
                'type'             => 'in',
            ]);
        }

        // 3. Pembayaran Invoice (Penerimaan Kas)
        foreach (InvoicePayment::with('invoice')->get() as $ip) {
            $refKey = InvoicePayment::class . '_' . $ip->id;
            $transactions->push([
                'id'               => 'ip_' . $ip->id,
                'source_type'      => InvoicePayment::class,
                'source_label'     => 'Pembayaran Invoice',
                'source_id'        => $ip->id,
                'date'             => $ip->payment_date,
                'description'      => '[PENERIMAAN KAS] Invoice ' . ($ip->invoice->invoice_number ?? '-'),
                'amount'           => $ip->amount,
                'reference_number' => 'INV-PAY-' . str_pad($ip->id, 4, '0', STR_PAD_LEFT),
                'is_journalled'    => $allJournalRefs->has($refKey),
                'type'             => 'in',
            ]);
        }

        // 4. Pembayaran Vendor Renewal
        foreach (VendorPayment::with('vendor')->get() as $vp) {
            $refKey = VendorPayment::class . '_' . $vp->id;
            $transactions->push([
                'id'               => 'vp_' . $vp->id,
                'source_type'      => VendorPayment::class,
                'source_label'     => 'Pembayaran Vendor',
                'source_id'        => $vp->id,
                'date'             => $vp->payment_date,
                'description'      => '[PEMBAYARAN VENDOR] ' . ($vp->vendor->name ?? '-'),
                'amount'           => $vp->amount,
                'reference_number' => 'VEND-' . str_pad($vp->id, 4, '0', STR_PAD_LEFT),
                'is_journalled'    => $allJournalRefs->has($refKey),
                'type'             => 'out',
            ]);
        }

        // 5. Reimburse (paid)
        foreach (ReimbursementRequest::where('status', 'paid')->with('user')->get() as $rb) {
            $refKey = ReimbursementRequest::class . '_' . $rb->id;
            $transactions->push([
                'id'               => 'rb_' . $rb->id,
                'source_type'      => ReimbursementRequest::class,
                'source_label'     => 'Reimbursement',
                'source_id'        => $rb->id,
                'date'             => $rb->paid_at ?? $rb->expense_date,
                'description'      => '[REIMBURSE] ' . ($rb->request_number ?? '-') . ' - ' . ($rb->user->name ?? ''),
                'amount'           => $rb->amount,
                'reference_number' => $rb->request_number ?? 'RB-' . $rb->id,
                'is_journalled'    => $allJournalRefs->has($refKey),
                'type'             => 'out',
            ]);
        }

        // 6. Konsumsi / Operasional (paid)
        foreach (OperationalRequest::where('status', 'paid')->with('user')->get() as $op) {
            $refKey = OperationalRequest::class . '_' . $op->id;
            $transactions->push([
                'id'               => 'op_' . $op->id,
                'source_type'      => OperationalRequest::class,
                'source_label'     => 'Biaya Operasional',
                'source_id'        => $op->id,
                'date'             => $op->paid_at ?? $op->activity_date,
                'description'      => '[OPERASIONAL] ' . ($op->request_number ?? '-') . ' - ' . ($op->activity_name ?? ''),
                'amount'           => $op->estimated_cost,
                'reference_number' => $op->request_number ?? 'OP-' . $op->id,
                'is_journalled'    => $allJournalRefs->has($refKey),
                'type'             => 'out',
            ]);
        }

        // 7. Perjalanan Dinas (settlement)
        foreach (BusinessTripSettlement::with('businessTripRequest.user')->whereHas('businessTripRequest')->get() as $bt) {
            $refKey = BusinessTripSettlement::class . '_' . $bt->id;
            $transactions->push([
                'id'               => 'bt_' . $bt->id,
                'source_type'      => BusinessTripSettlement::class,
                'source_label'     => 'Perjalanan Dinas',
                'source_id'        => $bt->id,
                'date'             => $bt->created_at,
                'description'      => '[PERJADIN] ' . ($bt->settlement_number ?? '-') . ' - ' . ($bt->businessTripRequest->user->name ?? ''),
                'amount'           => $bt->total_amount ?? 0,
                'reference_number' => $bt->settlement_number ?? 'BT-' . $bt->id,
                'is_journalled'    => $allJournalRefs->has($refKey),
                'type'             => 'out',
            ]);
        }

        // 8. Tagihan Bulanan (MonthlyBillPayment)
        foreach (MonthlyBillPayment::with('billType')->where('status', 'paid')->get() as $mb) {
            $refKey = MonthlyBillPayment::class . '_' . $mb->id;
            $transactions->push([
                'id'               => 'mb_' . $mb->id,
                'source_type'      => MonthlyBillPayment::class,
                'source_label'     => 'Tagihan Bulanan',
                'source_id'        => $mb->id,
                'date'             => $mb->payment_date,
                'description'      => '[TAGIHAN] ' . ($mb->billType->name ?? '-') . ' - ' . ($mb->period_label ?? ''),
                'amount'           => $mb->amount,
                'reference_number' => 'TAG-' . str_pad($mb->id, 4, '0', STR_PAD_LEFT),
                'is_journalled'    => $allJournalRefs->has($refKey),
                'type'             => 'out',
            ]);
        }

        // Filter pending
        $pendingTransactions = $transactions->where('is_journalled', false);
        if ($sourceType) {
            $pendingTransactions = $pendingTransactions->where('source_type', $sourceType);
        }
        if ($dateFrom) {
            $pendingTransactions = $pendingTransactions->filter(fn($t) => $t['date'] && $t['date'] >= $dateFrom);
        }
        if ($dateTo) {
            $pendingTransactions = $pendingTransactions->filter(fn($t) => $t['date'] && $t['date'] <= $dateTo);
        }
        $pendingTransactions = $pendingTransactions->sortByDesc('date')->values();

        $sourceTypes = [
            ['value' => CashTransaction::class,       'label' => 'Kas Operasional'],
            ['value' => Invoice::class,               'label' => 'Invoice / Piutang'],
            ['value' => InvoicePayment::class,        'label' => 'Pembayaran Invoice'],
            ['value' => VendorPayment::class,         'label' => 'Pembayaran Vendor (Renewal)'],
            ['value' => ReimbursementRequest::class,  'label' => 'Reimbursement'],
            ['value' => OperationalRequest::class,    'label' => 'Biaya Operasional (Konsumsi)'],
            ['value' => BusinessTripSettlement::class,'label' => 'Perjalanan Dinas'],
            ['value' => MonthlyBillPayment::class,    'label' => 'Tagihan Bulanan'],
        ];

        return Inertia::render('Accounting/Journals/Index', [
            'journals'            => $journals,
            'pendingTransactions' => $pendingTransactions,
            'coas'                => Coa::where('is_header', false)->orderBy('code')->get(),
            'periods'             => $periods,
            'activePeriodId'      => $defaultPeriodId,
            'filters'             => [
                'period_id'   => $periodId,
                'date_from'   => $dateFrom,
                'date_to'     => $dateTo,
                'source_type' => $sourceType,
            ],
            'sourceTypes'         => $sourceTypes,
        ]);
    }

    public function store(Request $request)
    {
        // Mode: Journal from source transaction (supports split items)
        if ($request->has('source_type')) {
            $request->validate([
                'source_type'    => 'required|string',
                'source_id'      => 'required|integer',
                'date'           => 'required|date',
                'description'    => 'required|string',
                'items'          => 'required|array|min:2',
                'items.*.coa_id' => 'required|exists:coas,id',
                'items.*.debit'  => 'required|numeric|min:0',
                'items.*.credit' => 'required|numeric|min:0',
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
            'date'           => 'required|date',
            'description'    => 'required|string',
            'items'          => 'required|array|min:2',
            'items.*.coa_id' => 'required|exists:coas,id',
            'items.*.debit'  => 'required|numeric|min:0',
            'items.*.credit' => 'required|numeric|min:0',
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

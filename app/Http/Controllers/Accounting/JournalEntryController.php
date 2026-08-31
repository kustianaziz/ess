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
use App\Models\AssetDepreciation;
use App\Models\Asset;
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

        // Build ref map (exclude voided) by grouping mapped reference IDs from JournalEntry
        $journalledRefs = JournalEntry::whereNotNull('reference_type')
            ->where('status', '!=', 'void')
            ->get(['reference_type', 'reference_id'])
            ->groupBy('reference_type')
            ->map(function ($items) {
                return $items->pluck('reference_id')->toArray();
            })
            ->toArray();

        $getUnjournalledIds = function ($class) use ($journalledRefs) {
            return $journalledRefs[$class] ?? [];
        };

        $shouldQuery = function($type) use ($sourceType) {
            if (!$sourceType) return true;
            return $sourceType === $type;
        };

        $transactions = collect();

        // 1. Kas Operasional — HANYA yang MANDIRI (source_type IS NULL = bukan turunan dari request lain)
        if ($shouldQuery(CashTransaction::class)) {
            $ctQuery = CashTransaction::where('status', 'posted')->whereNull('source_type');
            $exclude = $getUnjournalledIds(CashTransaction::class);
            if (!empty($exclude)) $ctQuery->whereNotIn('id', $exclude);
            if ($dateFrom) $ctQuery->where('transaction_date', '>=', $dateFrom);
            if ($dateTo)   $ctQuery->where('transaction_date', '<=', $dateTo);

            foreach ($ctQuery->get() as $ct) {
                $transactions->push([
                    'id'               => 'ct_' . $ct->id,
                    'source_type'      => CashTransaction::class,
                    'source_label'     => 'Kas Operasional',
                    'source_id'        => $ct->id,
                    'date'             => $ct->transaction_date,
                    'description'      => ($ct->type == 'in' ? '[KAS MASUK] ' : '[KAS KELUAR] ') . $ct->description,
                    'amount'           => $ct->amount,
                    'reference_number' => $ct->transaction_number ?? 'KAS-' . $ct->id,
                    'is_journalled'    => false,
                    'type'             => $ct->type,
                ]);
            }
        }

        // 2. Penerbitan Invoice (Piutang)
        if ($shouldQuery(Invoice::class . '_general') || $shouldQuery(Invoice::class . '_renewal')) {
            $invQuery = Invoice::whereIn('status', ['sent', 'partial', 'paid', 'overdue'])->with('customer');
            if ($dateFrom) $invQuery->where('invoice_date', '>=', $dateFrom);
            if ($dateTo)   $invQuery->where('invoice_date', '<=', $dateTo);

            foreach ($invQuery->get() as $inv) {
                $isRenewal = $inv->source_type === 'renewal';
                $classType = Invoice::class . ($isRenewal ? '_renewal' : '_general');

                if (!$shouldQuery($classType)) continue;

                $exclude = $getUnjournalledIds($classType);
                if (in_array($inv->id, $exclude)) continue;

                $transactions->push([
                    'id'               => 'inv_' . $inv->id,
                    'source_type'      => $classType,
                    'source_label'     => $isRenewal ? 'Invoice Renewal' : 'Invoice Tagihan',
                    'source_id'        => $inv->id,
                    'date'             => $inv->invoice_date,
                    'description'      => '[PIUTANG] ' . $inv->invoice_number . ' - ' . ($inv->customer->name ?? ''),
                    'amount'           => $inv->total_amount,
                    'reference_number' => $inv->invoice_number,
                    'is_journalled'    => false,
                    'type'             => 'in',
                    'original_source_type' => Invoice::class
                ]);
            }
        }

        // 3. Pembayaran Invoice (Penerimaan Kas)
        if ($shouldQuery(InvoicePayment::class . '_general') || $shouldQuery(InvoicePayment::class . '_renewal')) {
            $ipQuery = InvoicePayment::with('invoice');
            if ($dateFrom) $ipQuery->where('payment_date', '>=', $dateFrom);
            if ($dateTo)   $ipQuery->where('payment_date', '<=', $dateTo);

            foreach ($ipQuery->get() as $ip) {
                $isRenewal = $ip->invoice && $ip->invoice->source_type === 'renewal';
                $classType = InvoicePayment::class . ($isRenewal ? '_renewal' : '_general');

                if (!$shouldQuery($classType)) continue;

                $exclude = $getUnjournalledIds($classType);
                if (in_array($ip->id, $exclude)) continue;

                $transactions->push([
                    'id'               => 'ip_' . $ip->id,
                    'source_type'      => $classType,
                    'source_label'     => $isRenewal ? 'Pelunasan Renewal' : 'Pelunasan Tagihan',
                    'source_id'        => $ip->id,
                    'date'             => $ip->payment_date,
                    'description'      => '[PENERIMAAN KAS] Invoice ' . ($ip->invoice->invoice_number ?? '-'),
                    'amount'           => $ip->amount,
                    'reference_number' => 'INV-PAY-' . str_pad($ip->id, 4, '0', STR_PAD_LEFT),
                    'is_journalled'    => false,
                    'type'             => 'in',
                    'original_source_type' => InvoicePayment::class
                ]);
            }
        }

        // 4. Pembayaran Vendor Renewal
        if ($shouldQuery(VendorPayment::class)) {
            $vpQuery = VendorPayment::with('vendor');
            $exclude = $getUnjournalledIds(VendorPayment::class);
            if (!empty($exclude)) $vpQuery->whereNotIn('id', $exclude);
            if ($dateFrom) $vpQuery->where('payment_date', '>=', $dateFrom);
            if ($dateTo)   $vpQuery->where('payment_date', '<=', $dateTo);

            foreach ($vpQuery->get() as $vp) {
                $transactions->push([
                    'id'               => 'vp_' . $vp->id,
                    'source_type'      => VendorPayment::class,
                    'source_label'     => 'Pembayaran Vendor',
                    'source_id'        => $vp->id,
                    'date'             => $vp->payment_date,
                    'description'      => '[PEMBAYARAN VENDOR] ' . ($vp->vendor->name ?? '-'),
                    'amount'           => $vp->amount,
                    'reference_number' => 'VEND-' . str_pad($vp->id, 4, '0', STR_PAD_LEFT),
                    'is_journalled'    => false,
                    'type'             => 'out',
                ]);
            }
        }

        // 5. Reimburse (paid)
        if ($shouldQuery(ReimbursementRequest::class)) {
            $rbQuery = ReimbursementRequest::where('status', 'paid')->with('user');
            $exclude = $getUnjournalledIds(ReimbursementRequest::class);
            if (!empty($exclude)) $rbQuery->whereNotIn('id', $exclude);
            
            if ($dateFrom) {
                $rbQuery->where(function($q) use ($dateFrom) {
                    $q->where('paid_at', '>=', $dateFrom)->orWhere('expense_date', '>=', $dateFrom);
                });
            }
            if ($dateTo) {
                $rbQuery->where(function($q) use ($dateTo) {
                    $q->where('paid_at', '<=', $dateTo)->orWhere('expense_date', '<=', $dateTo);
                });
            }

            foreach ($rbQuery->get() as $rb) {
                $transactions->push([
                    'id'               => 'rb_' . $rb->id,
                    'source_type'      => ReimbursementRequest::class,
                    'source_label'     => 'Reimbursement',
                    'source_id'        => $rb->id,
                    'date'             => $rb->paid_at ?? $rb->expense_date,
                    'description'      => '[REIMBURSE] ' . ($rb->request_number ?? '-') . ' - ' . ($rb->user->name ?? ''),
                    'amount'           => $rb->amount,
                    'reference_number' => $rb->request_number ?? 'RB-' . $rb->id,
                    'is_journalled'    => false,
                    'type'             => 'out',
                ]);
            }
        }

        // 6. Konsumsi / Operasional (paid)
        if ($shouldQuery(OperationalRequest::class)) {
            $opQuery = OperationalRequest::where('status', 'paid')->with('user');
            $exclude = $getUnjournalledIds(OperationalRequest::class);
            if (!empty($exclude)) $opQuery->whereNotIn('id', $exclude);
            
            if ($dateFrom) {
                $opQuery->where(function($q) use ($dateFrom) {
                    $q->where('paid_at', '>=', $dateFrom)->orWhere('activity_date', '>=', $dateFrom);
                });
            }
            if ($dateTo) {
                $opQuery->where(function($q) use ($dateTo) {
                    $q->where('paid_at', '<=', $dateTo)->orWhere('activity_date', '<=', $dateTo);
                });
            }

            foreach ($opQuery->get() as $op) {
                $transactions->push([
                    'id'               => 'op_' . $op->id,
                    'source_type'      => OperationalRequest::class,
                    'source_label'     => 'Biaya Operasional',
                    'source_id'        => $op->id,
                    'date'             => $op->paid_at ?? $op->activity_date,
                    'description'      => '[OPERASIONAL] ' . ($op->request_number ?? '-') . ' - ' . ($op->activity_name ?? ''),
                    'amount'           => $op->estimated_cost,
                    'reference_number' => $op->request_number ?? 'OP-' . $op->id,
                    'is_journalled'    => false,
                    'type'             => 'out',
                ]);
            }
        }

        // 6.5. Pencairan Klaim Lembur (paid)
        if ($shouldQuery(\App\Models\OvertimeClaim::class)) {
            $lemburQuery = \App\Models\OvertimeClaim::where('status', 'paid')->with('user');
            $exclude = $getUnjournalledIds(\App\Models\OvertimeClaim::class);
            if (!empty($exclude)) $lemburQuery->whereNotIn('id', $exclude);
            
            if ($dateFrom) {
                $lemburQuery->where('paid_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $lemburQuery->where('paid_at', '<=', $dateTo);
            }

            foreach ($lemburQuery->get() as $claim) {
                $transactions->push([
                    'id'               => 'lembur_' . $claim->id,
                    'source_type'      => \App\Models\OvertimeClaim::class,
                    'source_label'     => 'Klaim Lembur',
                    'source_id'        => $claim->id,
                    'date'             => $claim->paid_at ?? $claim->created_at,
                    'description'      => '[KLAIM LEMBUR] ' . ($claim->claim_number ?? '-') . ' - ' . ($claim->user->name ?? ''),
                    'amount'           => $claim->amount,
                    'reference_number' => $claim->claim_number ?? 'LMBR-' . $claim->id,
                    'is_journalled'    => false,
                    'type'             => 'out',
                ]);
            }
        }

        // 7. Perjalanan Dinas (settlement)
        if ($shouldQuery(BusinessTripSettlement::class)) {
            $btQuery = BusinessTripSettlement::with('businessTripRequest.user')->whereHas('businessTripRequest');
            $exclude = $getUnjournalledIds(BusinessTripSettlement::class);
            if (!empty($exclude)) $btQuery->whereNotIn('id', $exclude);
            if ($dateFrom) $btQuery->where('created_at', '>=', $dateFrom);
            if ($dateTo)   $btQuery->where('created_at', '<=', $dateTo);

            foreach ($btQuery->get() as $bt) {
                $transactions->push([
                    'id'               => 'bt_' . $bt->id,
                    'source_type'      => BusinessTripSettlement::class,
                    'source_label'     => 'Perjalanan Dinas',
                    'source_id'        => $bt->id,
                    'date'             => $bt->created_at,
                    'description'      => '[PERJADIN] ' . ($bt->settlement_number ?? '-') . ' - ' . ($bt->businessTripRequest->user->name ?? ''),
                    'amount'           => $bt->total_actual_cost ?? 0,
                    'reference_number' => $bt->settlement_number ?? 'BT-' . $bt->id,
                    'is_journalled'    => false,
                    'type'             => 'out',
                ]);
            }
        }

        // 8. Tagihan Bulanan (MonthlyBillPayment)
        if ($shouldQuery(MonthlyBillPayment::class)) {
            $mbQuery = MonthlyBillPayment::with('billType')->where('status', 'paid');
            $exclude = $getUnjournalledIds(MonthlyBillPayment::class);
            if (!empty($exclude)) $mbQuery->whereNotIn('id', $exclude);
            if ($dateFrom) $mbQuery->where('payment_date', '>=', $dateFrom);
            if ($dateTo)   $mbQuery->where('payment_date', '<=', $dateTo);

            foreach ($mbQuery->get() as $mb) {
                $transactions->push([
                    'id'               => 'mb_' . $mb->id,
                    'source_type'      => MonthlyBillPayment::class,
                    'source_label'     => 'Tagihan Bulanan',
                    'source_id'        => $mb->id,
                    'date'             => $mb->payment_date,
                    'description'      => '[TAGIHAN] ' . ($mb->billType->name ?? '-') . ' (' . ($mb->billType->vendor_name ?? '-') . ') - ' . str_pad($mb->period_month, 2, '0', STR_PAD_LEFT) . '/' . $mb->period_year,
                    'amount'           => $mb->bill_amount,
                    'reference_number' => $mb->payment_reference ?? $mb->payment_number ?? ('TAG-' . $mb->id),
                    'is_journalled'    => false,
                    'type'             => 'out',
                ]);
            }
        }

        // 9. Penyusutan Aset (AssetDepreciation)
        if ($shouldQuery(AssetDepreciation::class)) {
            $depQuery = AssetDepreciation::with('asset');
            $exclude = $getUnjournalledIds(AssetDepreciation::class);
            if (!empty($exclude)) $depQuery->whereNotIn('id', $exclude);
            
            if ($dateFrom) {
                $depQuery->where(DB::raw("CONCAT(period_year, '-', LPAD(period_month, 2, '0'), '-01')"), '>=', $dateFrom);
            }
            if ($dateTo) {
                $depQuery->where(DB::raw("CONCAT(period_year, '-', LPAD(period_month, 2, '0'), '-01')"), '<=', $dateTo);
            }

            foreach ($depQuery->get() as $dep) {
                $transactions->push([
                    'id'               => 'dep_' . $dep->id,
                    'source_type'      => AssetDepreciation::class,
                    'source_label'     => 'Penyusutan Aset',
                    'source_id'        => $dep->id,
                    'date'             => sprintf('%04d-%02d-01', $dep->period_year, $dep->period_month),
                    'description'      => '[PENYUSUTAN] ' . ($dep->asset->name ?? 'Aset') . ' - ' . $dep->period_month . '/' . $dep->period_year,
                    'amount'           => $dep->depreciation_amount,
                    'reference_number' => 'DEP-' . str_pad($dep->id, 4, '0', STR_PAD_LEFT),
                    'is_journalled'    => false,
                    'type'             => 'out',
                ]);
            }
        }

        $pendingTransactions = $transactions->sortByDesc('date')->values();

        $sourceTypes = [
            ['value' => CashTransaction::class,       'label' => 'Kas Operasional (Mandiri)'],
            ['value' => Invoice::class . '_general',  'label' => 'Invoice Tagihan'],
            ['value' => Invoice::class . '_renewal',  'label' => 'Invoice Renewal'],
            ['value' => InvoicePayment::class . '_general', 'label' => 'Pelunasan Tagihan'],
            ['value' => InvoicePayment::class . '_renewal', 'label' => 'Pelunasan Renewal'],
            ['value' => VendorPayment::class,         'label' => 'Pembayaran Vendor (Renewal)'],
            ['value' => ReimbursementRequest::class,  'label' => 'Reimbursement'],
            ['value' => OperationalRequest::class,    'label' => 'Biaya Operasional (Konsumsi)'],
            ['value' => BusinessTripSettlement::class,'label' => 'Perjalanan Dinas'],
            ['value' => \App\Models\OvertimeClaim::class,'label' => 'Klaim Lembur'],
            ['value' => MonthlyBillPayment::class,    'label' => 'Tagihan Bulanan'],
            ['value' => AssetDepreciation::class,     'label' => 'Penyusutan Aset'],
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

        // Period check
        $period = \App\Models\AccountingPeriod::whereDate('start_date', '<=', $journal->date)
            ->whereDate('end_date', '>=', $journal->date)
            ->first();

        if ($period && $period->is_closed) {
            return back()->withErrors(['message' => 'Tidak dapat membatalkan jurnal pada periode yang sudah ditutup.']);
        }

        DB::transaction(function () use ($journal) {
            $journal->update(['status' => 'void']);
        });

        return back()->with('success', 'Jurnal ' . $journal->journal_number . ' berhasil dibatalkan dan dihapus dari buku besar.');
    }

    public function destroy(JournalEntry $journal)
    {
        // Period check
        $period = \App\Models\AccountingPeriod::whereDate('start_date', '<=', $journal->date)
            ->whereDate('end_date', '>=', $journal->date)
            ->first();

        if ($period && $period->is_closed) {
            return back()->withErrors(['message' => 'Tidak dapat menghapus jurnal pada periode yang sudah ditutup.']);
        }

        DB::transaction(function () use ($journal) {
            $journal->items()->delete();
            $journal->delete();
        });

        return back()->with('success', 'Jurnal ' . $journal->journal_number . ' berhasil dihapus secara permanen.');
    }

    public function show($id)
    {
        $journal = JournalEntry::with(['items.coa', 'creator', 'poster'])->findOrFail($id);
        return Inertia::render('Accounting/Journals/Show', [
            'journal' => $journal
        ]);
    }
}

<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Coa;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BeginningBalanceController extends Controller
{
    public function index()
    {
        $coas = Coa::orderBy('code')->get();
        
        $openingBalanceEntry = JournalEntry::with('items')
            ->where('is_opening_balance', true)
            ->first();

        $balances = [];
        if ($openingBalanceEntry) {
            foreach ($openingBalanceEntry->items as $item) {
                $balances[$item->coa_id] = [
                    'debit' => $item->debit,
                    'credit' => $item->credit,
                ];
            }
        }

        $coas->transform(function ($coa) use ($balances) {
            $coa->debit = $balances[$coa->id]['debit'] ?? 0;
            $coa->credit = $balances[$coa->id]['credit'] ?? 0;
            return $coa;
        });

        return Inertia::render('Accounting/BeginningBalances/Index', [
            'coas' => $coas,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'balances' => 'required|array',
            'balances.*.coa_id' => 'required|exists:coas,id',
            'balances.*.debit' => 'required|numeric|min:0',
            'balances.*.credit' => 'required|numeric|min:0',
            'date' => 'nullable|date',
        ]);

        $balances = collect($request->balances);

        $totalDebit = $balances->sum('debit');
        $totalCredit = $balances->sum('credit');

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return redirect()->back()->withErrors(['message' => 'Total Debit dan Kredit tidak seimbang (Selisih: ' . abs($totalDebit - $totalCredit) . ')']);
        }

        DB::beginTransaction();
        try {
            $existingEntry = JournalEntry::where('is_opening_balance', true)->first();
            if ($existingEntry) {
                $existingEntry->items()->delete();
                $existingEntry->delete();
            }

            $journalEntry = JournalEntry::create([
                'journal_number' => 'OB-' . time(),
                'date' => $request->date ?? now()->toDateString(),
                'description' => 'Neraca Awal',
                'status' => 'posted',
                'is_opening_balance' => true,
                'created_by' => auth()->id(),
            ]);

            foreach ($balances as $balance) {
                if ($balance['debit'] > 0 || $balance['credit'] > 0) {
                    JournalItem::create([
                        'journal_entry_id' => $journalEntry->id,
                        'coa_id' => $balance['coa_id'],
                        'debit' => $balance['debit'],
                        'credit' => $balance['credit'],
                        'description' => 'Neraca Awal',
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Neraca Awal berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['message' => 'Terjadi kesalahan saat menyimpan Neraca Awal: ' . $e->getMessage()]);
        }
    }
}

<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Coa;
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

        return Inertia::render('Accounting/Journals/Index', [
            'journals' => $journals,
            'coas' => Coa::orderBy('code')->get()
        ]);
    }

    public function store(Request $request)
    {
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

        DB::transaction(function () use ($request, $totalDebit) {
            // Generate journal number
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

<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AccountingPeriod;
use Inertia\Inertia;

class AccountingPeriodController extends Controller
{
    public function index()
    {
        $periods = AccountingPeriod::with('closedBy')->orderBy('start_date', 'desc')->get();
        return Inertia::render('Accounting/Periods/Index', [
            'periods' => $periods
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        AccountingPeriod::create($validated);

        return redirect()->route('accounting.periods.index')->with('success', 'Periode Akuntansi berhasil ditambahkan.');
    }

    public function update(Request $request, AccountingPeriod $period)
    {
        if ($period->is_closed) {
            return back()->with('error', 'Tidak dapat mengubah periode yang sudah ditutup.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $period->update($validated);

        return redirect()->route('accounting.periods.index')->with('success', 'Periode Akuntansi berhasil diperbarui.');
    }

    public function destroy(AccountingPeriod $period)
    {
        if ($period->is_closed) {
            return back()->with('error', 'Tidak dapat menghapus periode yang sudah ditutup.');
        }
        
        $period->delete();
        return redirect()->route('accounting.periods.index')->with('success', 'Periode Akuntansi berhasil dihapus.');
    }

    public function closePeriod(AccountingPeriod $period)
    {
        if ($period->is_closed) {
            return back()->with('error', 'Periode sudah ditutup sebelumnya.');
        }

        $period->update([
            'is_closed' => true,
            'closed_by' => auth()->id(),
            'closed_at' => now(),
        ]);

        return redirect()->route('accounting.periods.index')->with('success', 'Periode Akuntansi berhasil ditutup.');
    }
}

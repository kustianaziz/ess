<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialReportNote;
use Inertia\Inertia;

class FinancialNoteController extends Controller
{
    public function index(Request $request)
    {
        $notes = FinancialReportNote::orderBy('period_date', 'desc')->get();
        return Inertia::render('Accounting/Reports/Notes', [
            'notes' => $notes
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'period_date' => 'required|date',
            'content' => 'required|string',
        ]);

        FinancialReportNote::create($request->all());

        return redirect()->back()->with('success', 'Catatan berhasil ditambahkan.');
    }

    public function update(Request $request, FinancialReportNote $note)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'period_date' => 'required|date',
            'content' => 'required|string',
        ]);

        $note->update($request->all());

        return redirect()->back()->with('success', 'Catatan berhasil diperbarui.');
    }

    public function destroy(FinancialReportNote $note)
    {
        $note->delete();
        return redirect()->back()->with('success', 'Catatan berhasil dihapus.');
    }
}

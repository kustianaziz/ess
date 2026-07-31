<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseTypeController extends Controller
{
    public function index(): Response
    {
        $types = ExpenseType::latest()->get();

        return Inertia::render('Admin/ExpenseTypes/Index', [
            'expenseTypes' => $types,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_types,name',
            'is_active' => 'required|boolean',
        ]);

        ExpenseType::create($validated);

        return back()->with('success', 'Jenis pengeluaran berhasil ditambahkan!');
    }

    public function update(Request $request, ExpenseType $expenseType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => "required|string|max:255|unique:expense_types,name,{$expenseType->id}",
            'is_active' => 'required|boolean',
        ]);

        $expenseType->update($validated);

        return back()->with('success', 'Jenis pengeluaran berhasil diperbarui!');
    }

    public function destroy(ExpenseType $expenseType): RedirectResponse
    {
        $expenseType->delete();

        return back()->with('success', 'Jenis pengeluaran berhasil dihapus!');
    }
}

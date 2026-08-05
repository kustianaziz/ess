<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Coa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CoaController extends Controller
{
    public function index()
    {
        $coas = Coa::with('parent')->orderBy('code')->get();
        
        return Inertia::render('Accounting/Coas/Index', [
            'coas' => $coas
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coas,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:aset,hutang,modal,pendapatan,beban',
            'normal_balance' => 'required|in:debit,credit',
            'parent_id' => 'nullable|exists:coas,id',
            'is_active' => 'boolean',
            'description' => 'nullable|string'
        ]);

        $validated['is_active'] = $request->input('is_active', true);

        Coa::create($validated);

        return redirect()->back()->with('success', 'COA berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $coa = Coa::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|unique:coas,code,' . $coa->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:aset,hutang,modal,pendapatan,beban',
            'normal_balance' => 'required|in:debit,credit',
            'parent_id' => 'nullable|exists:coas,id',
            'is_active' => 'boolean',
            'description' => 'nullable|string'
        ]);

        $validated['is_active'] = $request->input('is_active', $coa->is_active);

        $coa->update($validated);

        return redirect()->back()->with('success', 'COA berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $coa = Coa::findOrFail($id);
        
        if (Coa::where('parent_id', $coa->id)->exists()) {
            return redirect()->back()->with('error', 'COA tidak dapat dihapus karena memiliki sub-akun (child).');
        }

        try {
            $coa->delete();
            return redirect()->back()->with('success', 'COA berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'COA tidak dapat dihapus karena sedang digunakan.');
        }
    }
}

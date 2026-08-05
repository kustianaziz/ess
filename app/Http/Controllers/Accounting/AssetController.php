<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Coa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function index()
    {
        $assets = Asset::with(['coaAsset', 'coaDepreciationExpense', 'coaAccumulatedDepreciation'])
            ->orderBy('id', 'desc')
            ->get();
            
        $coas = Coa::where('is_header', false)->orderBy('code')->get();

        return Inertia::render('Accounting/Assets/Index', [
            'assets' => $assets,
            'coas' => $coas
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_number' => 'required|string|unique:assets,asset_number',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0',
            'salvage_value' => 'required|numeric|min:0',
            'useful_life_years' => 'required|integer|min:1',
            'depreciation_method' => 'required|in:straight_line,declining_balance',
            'coa_asset_id' => 'required|exists:coas,id',
            'coa_depreciation_expense_id' => 'required|exists:coas,id',
            'coa_accumulated_depreciation_id' => 'required|exists:coas,id',
        ]);

        $validated['accumulated_depreciation'] = 0;
        $validated['book_value'] = $validated['purchase_price'];

        Asset::create($validated);

        return redirect()->back()->with('success', 'Aset berhasil ditambahkan.');
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'asset_number' => 'required|string|unique:assets,asset_number,' . $asset->id,
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0',
            'salvage_value' => 'required|numeric|min:0',
            'useful_life_years' => 'required|integer|min:1',
            'depreciation_method' => 'required|in:straight_line,declining_balance',
            'coa_asset_id' => 'required|exists:coas,id',
            'coa_depreciation_expense_id' => 'required|exists:coas,id',
            'coa_accumulated_depreciation_id' => 'required|exists:coas,id',
        ]);

        // If purchase_price changes, we might want to update the book value (though typically depreciation could affect it)
        // For simple update logic: recalculate book_value = purchase_price - accumulated_depreciation
        // Using existing accumulated_depreciation if we don't allow modifying it directly in this basic CRUD.
        $accumulated_depreciation = $asset->accumulated_depreciation;
        $validated['book_value'] = $validated['purchase_price'] - $accumulated_depreciation;

        $asset->update($validated);

        return redirect()->back()->with('success', 'Aset berhasil diperbarui.');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return redirect()->back()->with('success', 'Aset berhasil dihapus.');
    }
}

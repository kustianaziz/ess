<?php

namespace App\Http\Controllers\Renewal;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::latest()->get();
        return Inertia::render('Renewal/Vendors/Index', ['vendors' => $vendors]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:domain_registrar,hosting_provider,both,other',
            'contact_info' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);
        Vendor::create($validated);
        return redirect()->back()->with('success', 'Vendor berhasil ditambahkan.');
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:domain_registrar,hosting_provider,both,other',
            'contact_info' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);
        $vendor->update($validated);
        return redirect()->back()->with('success', 'Vendor berhasil diperbarui.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();
        return redirect()->back()->with('success', 'Vendor berhasil dihapus.');
    }
}

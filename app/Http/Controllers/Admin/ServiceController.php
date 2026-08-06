<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::latest()->get();
        return Inertia::render('Admin/Services/Index', [
            'services' => $services
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|max:2048',
            'signature_image' => 'nullable|image|max:2048',
            'signature_name' => 'nullable|string|max:255',
            'bank_credentials' => 'nullable|string',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('services/logos', 'public');
        }

        if ($request->hasFile('signature_image')) {
            $validated['signature_image'] = $request->file('signature_image')->store('services/signatures', 'public');
        }

        Service::create($validated);

        return redirect()->back()->with('success', 'Layanan berhasil ditambahkan.');
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|max:2048',
            'signature_image' => 'nullable|image|max:2048',
            'signature_name' => 'nullable|string|max:255',
            'bank_credentials' => 'nullable|string',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('services/logos', 'public');
        } else {
            // Keep existing if not uploaded
            unset($validated['logo']);
        }

        if ($request->hasFile('signature_image')) {
            $validated['signature_image'] = $request->file('signature_image')->store('services/signatures', 'public');
        } else {
            // Keep existing if not uploaded
            unset($validated['signature_image']);
        }

        $service->update($validated);

        return redirect()->back()->with('success', 'Layanan berhasil diperbarui.');
    }

    public function destroy(Service $service)
    {
        // Cegah hapus jika masih dipakai customer
        if ($service->customers()->count() > 0) {
            return redirect()->back()->withErrors(['error' => 'Layanan ini tidak bisa dihapus karena sedang digunakan oleh pelanggan. Silakan nonaktifkan (set tidak aktif) saja.']);
        }

        $service->delete();
        return redirect()->back()->with('success', 'Layanan berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers\Renewal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Domain;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DomainController extends Controller
{
    public function index()
    {
        $domains = Domain::with(['customer', 'vendor'])->latest()->get();
        return Inertia::render('Renewal/Domains/Index', [
            'domains' => $domains,
            'customers' => Customer::latest()->get(),
            'vendors' => Vendor::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vendor_id' => 'required|exists:vendors,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:domain,hosting,vps,email,other',
            'purchase_date' => 'required|date',
            'expired_date' => 'required|date',
            'price_customer' => 'required|numeric|min:0',
            'cost_vendor' => 'required|numeric|min:0',
            'auto_renew' => 'boolean',
        ]);
        Domain::create(array_merge($validated, ['status' => 'active']));
        return redirect()->back()->with('success', 'Domain/Hosting berhasil ditambahkan.');
    }

    public function update(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vendor_id' => 'required|exists:vendors,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:domain,hosting,vps,email,other',
            'purchase_date' => 'required|date',
            'expired_date' => 'required|date',
            'price_customer' => 'required|numeric|min:0',
            'cost_vendor' => 'required|numeric|min:0',
            'auto_renew' => 'boolean',
            'status' => 'required|in:active,expiring_soon,expired,cancelled',
        ]);
        $domain->update($validated);
        return redirect()->back()->with('success', 'Domain/Hosting berhasil diperbarui.');
    }

    public function destroy(Domain $domain)
    {
        $domain->delete();
        return redirect()->back()->with('success', 'Domain/Hosting berhasil dihapus.');
    }
}

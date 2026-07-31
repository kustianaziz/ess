<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DivisionController extends Controller
{
    public function index(): Response
    {
        $divisions = Division::withCount('users')->latest()->get();

        return Inertia::render('Admin/Divisions/Index', [
            'divisions' => $divisions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:divisions,name',
            'code' => 'nullable|string|max:50',
        ]);

        Division::create($validated);

        return back()->with('success', 'Divisi baru berhasil ditambahkan!');
    }

    public function update(Request $request, Division $division): RedirectResponse
    {
        $validated = $request->validate([
            'name' => "required|string|max:255|unique:divisions,name,{$division->id}",
            'code' => 'nullable|string|max:50',
        ]);

        $division->update($validated);

        return back()->with('success', 'Data divisi berhasil diperbarui!');
    }

    public function destroy(Division $division): RedirectResponse
    {
        if ($division->users()->count() > 0) {
            return back()->withErrors(['message' => 'Tidak dapat menghapus divisi yang masih memiliki karyawan.']);
        }

        $division->delete();

        return back()->with('success', 'Divisi berhasil dihapus!');
    }
}

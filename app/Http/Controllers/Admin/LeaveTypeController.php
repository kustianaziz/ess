<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    public function index(): Response
    {
        $types = LeaveType::latest()->get();

        return Inertia::render('Admin/LeaveTypes/Index', [
            'leaveTypes' => $types,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name',
            'default_quota' => 'required|integer|min:0',
            'requires_attachment' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        LeaveType::create($validated);

        return back()->with('success', 'Jenis cuti berhasil ditambahkan!');
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => "required|string|max:255|unique:leave_types,name,{$leaveType->id}",
            'default_quota' => 'required|integer|min:0',
            'requires_attachment' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        $leaveType->update($validated);

        return back()->with('success', 'Jenis cuti berhasil diperbarui!');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $leaveType->delete();

        return back()->with('success', 'Jenis cuti berhasil dihapus!');
    }
}

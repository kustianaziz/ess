<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityTypeController extends Controller
{
    public function index(): Response
    {
        $types = ActivityType::latest()->get();

        return Inertia::render('Admin/ActivityTypes/Index', [
            'activityTypes' => $types,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:activity_types,name',
            'is_active' => 'required|boolean',
        ]);

        ActivityType::create($validated);

        return back()->with('success', 'Jenis kegiatan operasional berhasil ditambahkan!');
    }

    public function update(Request $request, ActivityType $activityType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => "required|string|max:255|unique:activity_types,name,{$activityType->id}",
            'is_active' => 'required|boolean',
        ]);

        $activityType->update($validated);

        return back()->with('success', 'Jenis kegiatan operasional berhasil diperbarui!');
    }

    public function destroy(ActivityType $activityType): RedirectResponse
    {
        $activityType->delete();

        return back()->with('success', 'Jenis kegiatan operasional berhasil dihapus!');
    }
}

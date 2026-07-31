<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveBalanceController extends Controller
{
    public function index(Request $request): Response
    {
        $year = $request->input('year', date('Y'));

        $balances = LeaveBalance::with(['user.division', 'leaveType'])
            ->where('year', $year)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $users = User::where('status', 'active')->select('id', 'name', 'nik')->get();
        $leaveTypes = LeaveType::where('is_active', true)->get();

        return Inertia::render('Admin/LeaveBalances/Index', [
            'balances' => $balances,
            'users' => $users,
            'leaveTypes' => $leaveTypes,
            'year' => (int) $year,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer|min:2020|max:2099',
            'quota' => 'required|integer|min:0',
        ]);

        $exists = LeaveBalance::where('user_id', $validated['user_id'])
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', $validated['year'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['message' => 'Kuota cuti pengguna ini untuk jenis cuti dan tahun tersebut sudah ada.']);
        }

        LeaveBalance::create([
            'user_id' => $validated['user_id'],
            'leave_type_id' => $validated['leave_type_id'],
            'year' => $validated['year'],
            'quota' => $validated['quota'],
            'used' => 0,
            'remaining' => $validated['quota'],
        ]);

        return back()->with('success', 'Kuota cuti pengguna berhasil dibuat!');
    }

    public function update(Request $request, LeaveBalance $leaveBalance): RedirectResponse
    {
        $validated = $request->validate([
            'quota' => 'required|integer|min:0',
            'used' => 'required|integer|min:0',
        ]);

        $remaining = max(0, $validated['quota'] - $validated['used']);

        $leaveBalance->update([
            'quota' => $validated['quota'],
            'used' => $validated['used'],
            'remaining' => $remaining,
        ]);

        return back()->with('success', 'Kuota cuti pengguna berhasil diperbarui!');
    }
}

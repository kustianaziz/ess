<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $users = User::with(['division', 'manager', 'roles'])
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $divisions = Division::all();
        $managers = User::where('status', 'active')->select('id', 'name', 'position')->get();
        $roles = Role::all();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'divisions' => $divisions,
            'managers' => $managers,
            'roles' => $roles,
            'filters' => [
                'search' => $search ?? '',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nik' => 'required|string|unique:users,nik',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'division_id' => 'required|exists:divisions,id',
            'position' => 'required|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'role' => 'required|string|exists:roles,name',
            'status' => 'required|in:active,inactive',
        ]);

        $user = User::create([
            'nik' => $validated['nik'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'division_id' => $validated['division_id'],
            'position' => $validated['position'],
            'manager_id' => $validated['manager_id'] ?? null,
            'status' => $validated['status'],
        ]);

        $user->assignRole($validated['role']);

        return back()->with('success', "Pengguna {$user->name} berhasil ditambahkan!");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'nik' => ['required', 'string', Rule::unique('users')->ignore($user->id)],
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'division_id' => 'required|exists:divisions,id',
            'position' => 'required|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'role' => 'required|string|exists:roles,name',
            'status' => 'required|in:active,inactive',
        ]);

        $data = [
            'nik' => $validated['nik'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'division_id' => $validated['division_id'],
            'position' => $validated['position'],
            'manager_id' => $validated['manager_id'] ?? null,
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);
        $user->syncRoles([$validated['role']]);

        return back()->with('success', "Data pengguna {$user->name} berhasil diperbarui!");
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();
        return back()->with('success', 'Pengguna berhasil dihapus!');
    }
}

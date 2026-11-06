<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        $staff = User::query()
            ->where('is_admin', true)
            ->orderBy('name')
            ->get();

        return view('admin.staff.index', [
            'staff' => $staff,
            'roles' => config('security.admin.roles', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(array_keys(config('security.admin.roles', [])))],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ]);

        User::create([
            ...$data,
            'is_admin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.staff.index')->with('success', 'Administrator account created.');
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->is_admin, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(array_keys(config('security.admin.roles', [])))],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ]);

        if ($staff->is($request->user()) && ($data['role'] ?? null) !== $staff->role) {
            return back()->withErrors(['role' => 'You cannot change your own role.']);
        }

        if ($staff->is($request->user()) && $request->has('is_active') && ! $request->boolean('is_active')) {
            return back()->withErrors(['is_active' => 'You cannot deactivate your own account.']);
        }

        $staff->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $staff->is_active,
        ]);

        if (filled($data['password'] ?? null)) {
            $staff->password = $data['password'];
        }

        $staff->save();

        return redirect()->route('admin.staff.index')->with('success', 'Administrator account updated.');
    }

    public function destroy(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->is_admin, 404);

        if ($staff->is($request->user())) {
            return back()->withErrors(['staff' => 'You cannot delete your own administrator account.']);
        }

        $staff->forceFill(['is_active' => false])->save();

        return redirect()->route('admin.staff.index')->with('success', 'Administrator access deactivated.');
    }
}

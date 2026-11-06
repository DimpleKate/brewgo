<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request): RedirectResponse
    {
        return $this->startReset($request, false);
    }

    public function adminRequest(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function adminSend(Request $request): RedirectResponse
    {
        return $this->startReset($request, true);
    }

    private function startReset(Request $request, bool $admin): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $normalized = Str::lower(trim($validated['identifier']));

        $users = User::query()
            ->where(function ($query) use ($normalized) {
                $query->whereRaw('LOWER(email) = ?', [$normalized])
                    ->orWhereRaw('LOWER(name) = ?', [$normalized]);
            })
            ->where('is_admin', $admin)
            ->limit(2)
            ->get();

        if ($users->isEmpty()) {
            throw ValidationException::withMessages([
                'identifier' => $admin
                    ? 'We could not find an administrator account with that email or username.'
                    : 'We could not find an account with that email or username.',
            ]);
        }

        if ($users->count() > 1) {
            throw ValidationException::withMessages([
                'identifier' => 'More than one account matches that username. Please enter your email address instead.',
            ]);
        }

        $token = Str::random(64);

        $request->session()->put('password_reset', [
            'user_id' => $users->first()->id,
            'token' => $token,
            'admin' => $admin,
            'expires_at' => now()->addMinutes((int) config('auth.passwords.users.expire', 60))->timestamp,
        ]);

        return redirect()->route($admin ? 'admin.password.reset' : 'password.reset', ['token' => $token]);
    }
}

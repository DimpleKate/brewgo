<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $login = Str::lower($request->string('email')->trim()->toString());

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$login])
            ->orWhereRaw('LOWER(name) = ?', [$login])
            ->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid administrator credentials.',
            ]);
        }

        if (! $user->is_admin) {
            throw ValidationException::withMessages([
                'email' => 'This account does not have administrator access.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'This administrator account is deactivated.',
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Verify your administrator email address before signing in.',
            ]);
        }

        if ($user->hasTwoFactorEnabled()) {
            Auth::logout();
            $request->session()->put('admin_2fa_pending', [
                'id' => $user->id,
                'remember' => $request->boolean('remember'),
                'expires_at' => now()->addMinutes(10)->timestamp,
            ]);
            $request->session()->regenerate();

            return redirect()->route('admin.two-factor.challenge')->with('status', 'Enter the 6-digit code from your authenticator app.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'))->with('success', 'Welcome to the admin console.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Admin session ended.');
    }
}

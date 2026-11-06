<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $this->pendingUserId($request)) {
            return redirect()->route('admin.login')->with('error', 'Start by signing in with your administrator credentials.');
        }

        return view('admin.auth.two-factor-challenge');
    }

    public function store(Request $request, TotpService $totp): RedirectResponse
    {
        $request->validate([
            'code' => ['nullable', 'string', 'max:20'],
            'recovery_code' => ['nullable', 'string', 'max:40'],
        ]);

        $pending = $request->session()->get('admin_2fa_pending');
        $user = User::query()->whereKey($this->pendingUserId($request))->first();

        if (! $pending || ! $user || ! $user->is_active || ! $user->is_admin) {
            $request->session()->forget('admin_2fa_pending');

            return redirect()->route('admin.login')->with('error', 'Your sign-in challenge expired. Please sign in again.');
        }

        $valid = false;

        if (filled($request->input('recovery_code'))) {
            $valid = $user->useRecoveryCode((string) $request->input('recovery_code'));
        } elseif (filled($request->input('code')) && $user->hasTwoFactorEnabled()) {
            $valid = $totp->verify((string) $user->two_factor_secret, (string) $request->input('code'));
        }

        if (! $valid) {
            return back()->withErrors(['code' => 'That authentication code is not valid. Try the next code or use a recovery code.']);
        }

        Auth::loginUsingId($user->id, (bool) ($pending['remember'] ?? false));
        $request->session()->forget('admin_2fa_pending');
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'))->with('success', 'Welcome to the admin console.');
    }

    private function pendingUserId(Request $request): ?int
    {
        $pending = $request->session()->get('admin_2fa_pending');

        if (! is_array($pending) || empty($pending['id']) || empty($pending['expires_at'])) {
            return null;
        }

        if (now()->timestamp > (int) $pending['expires_at']) {
            $request->session()->forget('admin_2fa_pending');

            return null;
        }

        return (int) $pending['id'];
    }
}

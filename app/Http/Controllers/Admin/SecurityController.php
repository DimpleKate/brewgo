<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.security', [
            'user' => $request->user(),
        ]);
    }

    public function enable(Request $request, TotpService $totp): RedirectResponse
    {
        $user = $request->user();

        $secret = $totp->generateSecret();
        $codes = $totp->recoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('admin.security.edit')->with('success', 'Scan the setup key in your authenticator app, then confirm with a 6-digit code.')->with('two_factor_setup', [
            'secret' => $secret,
            'uri' => $totp->provisioningUri(config('app.name', 'Brew & Go'), $user->email, $secret),
            'recovery_codes' => $codes,
        ]);
    }

    public function confirm(Request $request, TotpService $totp): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:10'],
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            return redirect()->route('admin.security.edit')->with('error', 'Start two-factor setup first.');
        }

        if (! $totp->verify((string) $user->two_factor_secret, (string) $request->input('code'))) {
            return back()->withErrors(['code' => 'That code is not valid. Check your authenticator app and try again.']);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return redirect()->route('admin.security.edit')
            ->with('success', 'Two-factor authentication is now enabled.')
            ->with('two_factor_recovery_codes', $user->recoveryCodes());
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('admin.security.edit')->with('success', 'Two-factor authentication has been disabled.');
    }

    public function regenerateRecoveryCodes(Request $request, TotpService $totp): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.security.edit')->with('error', 'Enable two-factor authentication before generating recovery codes.');
        }

        $codes = $totp->recoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return redirect()->route('admin.security.edit')
            ->with('success', 'New recovery codes generated. Store them somewhere safe.')
            ->with('two_factor_recovery_codes', $codes);
    }
}

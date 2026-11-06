<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        return $this->showResetForm($request, false);
    }

    public function adminCreate(Request $request): View|RedirectResponse
    {
        return $this->showResetForm($request, true);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->updatePassword($request, false);
    }

    public function adminStore(Request $request): RedirectResponse
    {
        return $this->updatePassword($request, true);
    }

    private function showResetForm(Request $request, bool $admin): View|RedirectResponse
    {
        $reset = $this->resetContext($request, $admin);
        $token = (string) $request->route('token');

        if ($reset === null || ! hash_equals($reset['token'], $token)) {
            return redirect()
                ->route($admin ? 'admin.password.request' : 'password.request')
                ->withErrors([
                    'identifier' => 'Your password reset session has expired. Please enter your email or username again.',
                ]);
        }

        return view($admin ? 'admin.auth.reset-password' : 'auth.reset-password', [
            'token' => $reset['token'],
            'account' => $reset['user']->email,
        ]);
    }

    private function updatePassword(Request $request, bool $admin): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->mixedCase()->numbers()],
        ]);

        $reset = $this->resetContext($request, $admin);

        if ($reset === null || ! hash_equals($reset['token'], $request->string('token')->toString())) {
            throw ValidationException::withMessages([
                'token' => 'Your password reset session has expired. Please enter your email or username again.',
            ]);
        }

        $user = $reset['user'];

        $user->forceFill([
            'password' => Hash::make($request->string('password')->toString()),
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->forget('password_reset');
        $request->session()->regenerateToken();

        event(new PasswordReset($user));

        return redirect()
            ->route($admin ? 'admin.login' : 'login')
            ->with('success', 'Your password has been reset. You can now sign in.');
    }

    /**
     * @return array{user: User, token: string}|null
     */
    private function resetContext(Request $request, bool $admin): ?array
    {
        $reset = $request->session()->get('password_reset');

        if (
            ! is_array($reset)
            || ! isset($reset['user_id'], $reset['token'], $reset['admin'], $reset['expires_at'])
            || (bool) $reset['admin'] !== $admin
            || now()->timestamp > (int) $reset['expires_at']
        ) {
            $request->session()->forget('password_reset');

            return null;
        }

        $user = User::find($reset['user_id']);

        if (! $user) {
            $request->session()->forget('password_reset');

            return null;
        }

        return [
            'user' => $user,
            'token' => (string) $reset['token'],
        ];
    }
}

@extends('layouts.admin')

@section('title', 'Security')

@section('content')
@php($setup = session('two_factor_setup'))
@php($newCodes = session('two_factor_recovery_codes'))

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Account security</h1>
        <p class="admin-page-subtitle text-muted-bp">Protect your administrator account with two-factor authentication.</p>
    </div>
    <span class="badge {{ $user->hasTwoFactorEnabled() ? 'text-bg-success' : 'text-bg-warning' }} align-self-start">
        {{ $user->hasTwoFactorEnabled() ? '2FA enabled' : '2FA not enabled' }}
    </span>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="admin-card mb-3">
            <div class="footer-title">Two-factor authentication</div>
            <p class="small text-muted-bp">Use an authenticator app such as Google Authenticator or Authy. Every administrator sign-in will ask for a 6-digit code.</p>

            @if($setup)
                <div class="alert alert-info">
                    <div class="fw-semibold mb-1">Setup key</div>
                    <code class="text-break">{{ $setup['secret'] }}</code>
                    <div class="small mt-2">Open this URI in your authenticator app if you cannot scan a QR code:</div>
                    <code class="small text-break d-block">{{ $setup['uri'] }}</code>
                </div>

                @if(! empty($setup['recovery_codes']))
                    <div class="alert alert-warning">
                        <div class="fw-semibold mb-1">Recovery codes</div>
                        <p class="small mb-2">Store these somewhere safe. Each code works once if you lose your authenticator.</p>
                        <div class="row row-cols-2 g-1">
                            @foreach($setup['recovery_codes'] as $code)
                                <div class="col"><code>{{ $code }}</code></div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form action="{{ route('admin.security.two-factor.confirm') }}" method="POST" data-submit-once>
                    @csrf
                    <label class="form-label" for="code">6-digit code</label>
                    <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="10" class="form-control @error('code') is-invalid @enderror" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <button class="btn btn-gold btn-sm mt-3" type="submit">Confirm and enable 2FA</button>
                </form>
            @elseif(! $user->hasTwoFactorEnabled())
                <form action="{{ route('admin.security.two-factor.enable') }}" method="POST">
                    @csrf
                    <button class="btn btn-gold btn-sm" type="submit">Start 2FA setup</button>
                </form>
            @else
                <div class="alert alert-success small mb-3">Two-factor authentication is active on this account.</div>

                @if($newCodes)
                    <div class="alert alert-warning">
                        <div class="fw-semibold mb-1">New recovery codes</div>
                        <p class="small mb-2">These codes replace any earlier codes. Store them somewhere safe.</p>
                        <div class="row row-cols-2 g-1">
                            @foreach($newCodes as $code)
                                <div class="col"><code>{{ $code }}</code></div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <form action="{{ route('admin.security.two-factor.recovery-codes') }}" method="POST" data-submit-once>
                            @csrf
                            <label class="form-label" for="recoveryPassword">Password</label>
                            <input id="recoveryPassword" name="password" type="password" autocomplete="current-password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <button class="btn btn-soft btn-sm w-100 mt-2" type="submit">Regenerate recovery codes</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form action="{{ route('admin.security.two-factor.disable') }}" method="POST" data-submit-once data-confirm="Disable two-factor authentication?">
                            @csrf
                            @method('DELETE')
                            <label class="form-label" for="disablePassword">Password</label>
                            <input id="disablePassword" name="password" type="password" autocomplete="current-password" class="form-control" required>
                            <button class="btn btn-outline-danger btn-sm w-100 mt-2" type="submit">Disable 2FA</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="col-xl-5">
        <div class="admin-card">
            <div class="footer-title">Account</div>
            <div class="d-flex align-items-center gap-3">
                @include('partials.avatar', ['user' => $user, 'size' => 'lg'])
                <div>
                    <div class="text-cream fw-semibold">{{ $user->name }}</div>
                    <div class="small text-muted-bp">{{ $user->email }}</div>
                    <div class="small text-gold">{{ $user->roleLabel() }}</div>
                </div>
            </div>
            <div class="d-grid gap-2 mt-3">
                <a class="btn btn-soft btn-sm" href="{{ route('admin.profile.edit') }}"><i class="bi bi-person me-1"></i>Edit profile</a>
                <a class="btn btn-soft btn-sm" href="{{ route('admin.staff.index') }}"><i class="bi bi-people me-1"></i>Manage administrators</a>
            </div>
            <p class="small text-muted-bp mt-3 mb-0">If you lose access to your authenticator and recovery codes, another super administrator must reset your account.</p>
        </div>
    </div>
</div>
@endsection

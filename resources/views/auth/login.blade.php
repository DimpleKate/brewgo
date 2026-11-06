@extends('layouts.auth')

@section('title', 'Log In | '.$site['brand_name'])

@section('content')
<form action="{{ route('login.store') }}" method="POST" data-submit-once>
    @csrf

    <div class="mb-3">
        <label class="form-label form-label--auth" for="email">Email or Username</label>
        <div class="input-group-auth">
            <span class="input-group-auth-icon"><i class="bi bi-envelope"></i></span>
            <input id="email"
                   type="text"
                   name="email"
                   class="form-control form-control-auth @error('email') is-invalid @enderror"
                   value="{{ old('email') }}"
                   required
                   autofocus
                   autocomplete="username email"
                   placeholder="Enter your email or username">
        </div>
        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label form-label--auth" for="password">Password</label>
        <div class="input-group-auth password-field">
            <span class="input-group-auth-icon"><i class="bi bi-lock"></i></span>
            <input id="password"
                   type="password"
                   name="password"
                   class="form-control form-control-auth @error('password') is-invalid @enderror"
                   required
                   autocomplete="current-password"
                   placeholder="Enter your password">
            <button class="password-toggle password-toggle-auth" type="button" data-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false" title="Show password">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div class="form-check form-check-auth">
            <input id="remember" type="checkbox" name="remember" value="1" class="form-check-input" @checked(old('remember'))>
            <label class="form-check-label" for="remember">Remember Me</label>
        </div>
        <a href="{{ route('password.request') }}" class="auth-link-muted">Forgot Password</a>
    </div>

    <button class="btn btn-auth w-100" type="submit">Log In</button>
</form>

<div class="auth-footer-links">
    <div class="auth-footer-row">
        <span>Don't have an account?</span>
        <a href="{{ route('register') }}">Register</a>
    </div>
    <div class="auth-footer-row mt-2">
        <a href="{{ route('home') }}"><i class="bi bi-arrow-left me-1"></i>Back to Homepage</a>
    </div>
</div>
@endsection

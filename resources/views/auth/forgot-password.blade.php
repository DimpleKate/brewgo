@extends('layouts.auth')

@section('title', 'Reset your password | '.$site['brand_name'])

@section('content')
<div class="text-center mb-3">
    <h2 class="auth-card-title h5">Forgot your password?</h2>
    <p class="auth-card-subtitle mb-0">Enter the email address or username linked to your account.</p>
</div>

@if(session('status'))
    <div class="alert alert-success small">{{ session('status') }}</div>
@endif

<form action="{{ route('password.email') }}" method="POST" data-submit-once>
    @csrf

    <div class="mb-3">
        <label class="form-label form-label--auth" for="identifier">Email or username</label>
        <div class="input-group-auth">
            <span class="input-group-auth-icon"><i class="bi bi-person"></i></span>
            <input id="identifier" type="text" name="identifier" value="{{ old('identifier') }}" class="form-control form-control-auth @error('identifier') is-invalid @enderror" required autofocus autocomplete="username email" placeholder="Enter your email or username">
        </div>
        @error('identifier')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <button class="btn btn-auth w-100" type="submit">Continue</button>
</form>

<div class="auth-footer-links">
    <div class="auth-footer-row">
        <a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a>
    </div>
</div>
@endsection

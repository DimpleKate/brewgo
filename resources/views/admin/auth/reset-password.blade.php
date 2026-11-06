@extends('layouts.auth')

@section('title', 'Administrator password reset | '.$site['brand_name'])

@section('content')
<div class="text-center mb-3">
    <h2 class="auth-card-title h5">Choose a new administrator password</h2>
    <p class="auth-card-subtitle mb-0">Use at least 8 characters with upper and lowercase letters and numbers.</p>
</div>

@error('token')
    <div class="alert alert-danger small">{{ $message }}</div>
@enderror

<form action="{{ route('admin.password.update') }}" method="POST" data-submit-once>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="mb-3">
        <label class="form-label form-label--auth" for="account">Administrator account</label>
        <input id="account" type="text" value="{{ $account }}" class="form-control form-control-auth" disabled>
    </div>

    <div class="mb-3">
        <label class="form-label form-label--auth" for="password">New password</label>
        <div class="password-field">
            <input id="password" type="password" name="password" class="form-control form-control-auth @error('password') is-invalid @enderror" required autocomplete="new-password">
            <button class="password-toggle password-toggle-auth" type="button" data-password-toggle aria-controls="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label form-label--auth" for="passwordConfirmation">Confirm new password</label>
        <input id="passwordConfirmation" type="password" name="password_confirmation" class="form-control form-control-auth" required autocomplete="new-password">
    </div>

    <button class="btn btn-auth w-100" type="submit">Reset administrator password</button>
</form>
@endsection

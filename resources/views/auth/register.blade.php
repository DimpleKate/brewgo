@extends('layouts.auth')

@section('title', 'Create Account | '.$site['brand_name'])

@section('content')
<span class="eyebrow">{{ $site['auth_register_eyebrow'] }}</span>
<h1>{{ $site['auth_register_title'] }} <span class="gold-text">{{ $site['auth_register_accent'] }}</span></h1>
<p class="text-muted-bp mb-4">{{ $site['auth_register_copy'] }}</p>

<form action="{{ route('register.store') }}" method="POST" data-submit-once novalidate class="auth-form-balanced">
    @csrf

    <div class="mb-3">
        <label class="form-label" for="name">Full name</label>
        <input id="name"
               type="text"
               name="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}"
               required
               autofocus
               maxlength="100"
               autocomplete="name"
               placeholder="Your full name">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="email">Email address</label>
        <input id="email"
               type="email"
               name="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email') }}"
               required
               maxlength="255"
               autocomplete="email"
               placeholder="brewgo@gmail.com">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="phone">Phone</label>
        <input id="phone"
               type="tel"
               name="phone"
               class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone') }}"
               required
               maxlength="30"
               autocomplete="tel"
               inputmode="tel"
               placeholder="0917 123 4567">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <div class="password-field">
            <input id="password"
                   type="password"
                   name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required
                   autocomplete="new-password"
                   placeholder="At least 8 characters">
            @include('partials.password-toggle', ['target' => 'password'])
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text mt-1">Include upper and lowercase letters and numbers.</div>
    </div>

    <div class="mb-4">
        <label class="form-label" for="passwordConfirmation">Confirm password</label>
        <div class="password-field">
            <input id="passwordConfirmation"
                   type="password"
                   name="password_confirmation"
                   class="form-control"
                   required
                   autocomplete="new-password"
                   placeholder="Repeat your password">
            @include('partials.password-toggle', ['target' => 'passwordConfirmation'])
        </div>
    </div>

    <button class="btn btn-gold w-100" type="submit">Create account <i class="bi bi-arrow-right ms-1"></i></button>
</form>

<div class="text-center mt-4">
    <span class="small text-muted-bp">{{ $site['auth_register_footer_prompt'] }}</span>
    <a class="small text-gold ms-1" href="{{ route('login') }}">{{ $site['auth_register_footer_link'] }}</a>
</div>

<div class="text-center mt-3">
    <a class="small text-muted-bp" href="{{ route('home') }}"><i class="bi bi-arrow-left me-1"></i>Back to the café</a>
</div>
@endsection

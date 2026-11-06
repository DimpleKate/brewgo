@extends('layouts.auth')

@section('title', 'Verify your email | '.$site['brand_name'])

@section('content')
<div class="text-center mb-3">
    <h2 class="auth-card-title h5">Verify your email address</h2>
    <p class="auth-card-subtitle mb-0">
        We sent a verification link to <strong>{{ auth()->user()->email }}</strong>.
        Open the link to finish setting up your account.
    </p>
</div>

@if(session('status') === 'verification-link-sent')
    <div class="alert alert-success small">A fresh verification link has been sent to your email address.</div>
@endif

<div class="alert alert-info small">
    You can browse the menu and build your cart right away. Email verification is required before checkout,
    order history, receipts, and account tools become available.
</div>

<form action="{{ route('verification.send') }}" method="POST" data-submit-once>
    @csrf
    <button class="btn btn-auth w-100" type="submit">Resend verification email</button>
</form>

<div class="auth-footer-links">
    <div class="auth-footer-row">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button class="btn btn-link p-0" type="submit">Sign out</button>
        </form>
    </div>
</div>
@endsection

<!DOCTYPE html>
<html lang="en" data-theme="dark" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $site['brand_name'] ?? 'Brew & Go')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brew-go-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brew-go-logo.png') }}">
    @include('partials.theme-init')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-page auth-page--login">
    <div class="auth-bg" @if($site['auth_image_url'] ?? null) style="--auth-bg-image: url('{{ $site['auth_image_url'] }}');" @else style="--auth-bg-image: url('{{ asset('images/auth-bg-coffee-beans.jpg') }}');" @endif></div>
    <div class="auth-overlay"></div>

    <main class="auth-center">
        <div class="auth-card auth-card--center">
            <div class="auth-card-header text-center">
                <div class="auth-lock-icon">
                    <i class="bi bi-lock-fill"></i>
                </div>
                <h1 class="auth-card-title">Welcome Back</h1>
                <p class="auth-card-subtitle">Sign in to continue to Brew & Go</p>
            </div>

            @include('partials.flash')

            @yield('content')
        </div>
    </main>

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>

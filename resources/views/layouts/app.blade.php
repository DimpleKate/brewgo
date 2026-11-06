<!DOCTYPE html>
<html lang="en" data-theme="dark" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $site['seo_description'] ?? '' }}">
    <title>@yield('title', $site['home_meta_title'] ?? $site['brand_name'])</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brew-go-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brew-go-logo.png') }}">
    @include('partials.theme-init')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="@yield('body-class')">
    @include('partials.navbar')

    <main>
        <div class="container pt-3">
            @include('partials.flash')
        </div>
        @yield('content')
    </main>

    @include('partials.footer')

    @unless(auth()->check() && auth()->user()->isAdmin())
        @include('partials.chatbot')
    @endunless
    @include('partials.live-sync-toast')

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>

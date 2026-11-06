<!DOCTYPE html>
<html lang="en" data-theme="dark" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Something went wrong') | Brew &amp; Go</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brew-go-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brew-go-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="section-pad">
        <div class="container" style="max-width: 720px;">
            <div class="panel panel-pad text-center">
                <div class="feature-icon mx-auto mb-3"><i class="bi @yield('icon', 'bi-exclamation-triangle')" aria-hidden="true"></i></div>
                <div class="eyebrow">@yield('code')</div>
                <h1 class="section-title fs-2">@yield('heading')</h1>
                <p class="text-muted-bp mb-4">@yield('message')</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a class="btn btn-gold" href="{{ route('home') }}">Back to the storefront</a>
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a class="btn btn-outline-gold" href="{{ route('admin.dashboard') }}">Admin dashboard</a>
                        @endif
                    @endauth
                </div>
                @hasSection('detail')
                    <p class="small text-muted-bp mt-4 mb-0">@yield('detail')</p>
                @endif
            </div>
        </div>
    </main>
</body>
</html>

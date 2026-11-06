<!DOCTYPE html>
<html lang="en" data-theme="dark" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin') | {{ $site['brand_name'] ?? 'Brew & Go' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brew-go-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brew-go-logo.png') }}">
    @include('partials.theme-init')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script>
        (() => {
            try {
                if (localStorage.getItem('brewgo-admin-sidebar') === 'collapsed') {
                    document.documentElement.classList.add('admin-sidebar-collapsed');
                }
            } catch (error) {
            }
        })();
    </script>
</head>
<body class="admin-body">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-head">
            <a class="navbar-brand-bp" href="{{ route('admin.dashboard') }}">
                <span class="brand-mark">
                    <img src="{{ asset('images/brew-go-logo.png') }}" alt="{{ $site['brand_name'] }} logo" class="brand-logo">
                </span>
                <span>
                    <span class="brand-name">{{ mb_strtoupper($site['brand_name']) }}</span>
                    <span class="brand-tag">Admin Console</span>
                </span>
            </a>
            <button class="admin-sidebar-toggle" type="button" data-admin-sidebar-toggle aria-controls="adminSidebar" aria-expanded="true" aria-label="Collapse sidebar" title="Collapse sidebar">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
        </div>

        @include('admin.partials.nav')

        <div class="admin-sidebar-footer">
            <div class="admin-avatar">
                <a class="admin-avatar-link" href="{{ route('admin.profile.edit') }}" aria-label="Open your profile">
                    @include('partials.avatar', ['user' => auth()->user(), 'size' => 'md'])
                </a>
                <div class="flex-grow-1 min-w-0">
                    <a class="admin-avatar-link" href="{{ route('admin.profile.edit') }}">
                        <div class="small text-cream text-truncate">{{ auth()->user()->name }}</div>
                        <small>{{ auth()->user()->email }}</small>
                    </a>
                </div>
                @include('partials.theme-toggle')
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-sm text-muted-bp border-0" type="submit" aria-label="Sign out" data-bs-toggle="tooltip" title="Sign out">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="offcanvas offcanvas-start admin-offcanvas" tabindex="-1" id="adminNav" aria-labelledby="adminNavLabel">
        <div class="offcanvas-header">
            <div class="d-flex align-items-center gap-2" id="adminNavLabel">
                <span class="brand-mark">
                    <img src="{{ asset('images/brew-go-logo.png') }}" alt="{{ $site['brand_name'] }} logo" class="brand-logo">
                </span>
                <span class="brand-name fs-6">{{ mb_strtoupper($site['brand_name']) }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            @include('admin.partials.nav')

            <div class="admin-sidebar-footer">
                <div class="admin-avatar mb-2">
                    @include('partials.avatar', ['user' => auth()->user(), 'size' => 'md'])
                    <div class="flex-grow-1 min-w-0">
                        <div class="small text-cream text-truncate">{{ auth()->user()->name }}</div>
                        <small>{{ auth()->user()->email }}</small>
                    </div>
                </div>
                <a class="btn btn-soft btn-sm w-100 mb-2" href="{{ route('admin.profile.edit') }}"><i class="bi bi-person me-2"></i>My profile</a>
                @include('partials.theme-toggle')
                <form action="{{ route('admin.logout') }}" method="POST" class="mt-2">
                    @csrf
                    <button class="btn btn-soft btn-sm w-100" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
                </form>
            </div>
        </div>
    </div>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-soft btn-sm admin-nav-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminNav" aria-controls="adminNav" aria-label="Open admin navigation">
                    <i class="bi bi-list"></i>
                </button>
                <div class="admin-topbar-context">
                    <span class="admin-topbar-eyebrow">Admin console</span>
                    <span class="admin-topbar-page">@yield('title', 'Dashboard')</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="admin-topbar-date d-none d-md-inline">{{ now()->format('D, M d') }}</span>
                @include('partials.theme-toggle')
                <div class="admin-topbar-user">
                    <a class="admin-avatar-link d-flex align-items-center gap-2" href="{{ route('admin.profile.edit') }}" aria-label="Open your profile">
                        @include('partials.avatar', ['user' => auth()->user(), 'size' => 'md'])
                        <span class="admin-topbar-user-name d-none d-lg-inline">{{ auth()->user()->name }}</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="admin-content">
            @include('partials.flash')

            @yield('content')
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>

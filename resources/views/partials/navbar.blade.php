@php($isAdminArea = request()->is('admin', 'admin/*'))
@php($isAdminUser = auth()->check() && auth()->user()->isAdmin())
@php($showCustomerNav = ! $isAdminArea && ! $isAdminUser)

<nav class="navbar navbar-expand-lg navbar-bp sticky-top">
    <div class="container">
        <a class="navbar-brand-bp" href="{{ route('home') }}">
            <span class="brand-mark">
                <img src="{{ asset('images/brew-go-logo.png') }}" alt="{{ $site['brand_name'] }} logo" class="brand-logo">
            </span>
            <span>
                <span class="brand-name">{{ mb_strtoupper($site['brand_name']) }}</span>
                <span class="brand-tag">{{ $site['brand_tagline'] }}</span>
            </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                @if($showCustomerNav)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}#home" data-section-link="home">Home</a>
                    </li>
                    @auth
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('menu.*') ? 'active' : '' }}" href="{{ route('menu.index') }}">Menu</a>
                        </li>
                    @endauth
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('home') }}#about" data-section-link="about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('home') }}#contact" data-section-link="contact">Contact</a>
                    </li>
                @endif
                @auth
                    @unless($isAdminUser)
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">My Orders</a>
                        </li>
                    @endunless
                    @if(auth()->user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.dashboard') }}">Admin</a>
                        </li>
                    @endif
                @endauth
            </ul>

            <div class="nav-auth-area d-flex align-items-center gap-1 gap-lg-2">
                @guest
                    @include('partials.theme-toggle')
                    <a class="nav-icon-link" href="{{ route('login') }}">Sign in</a>
                    <a class="btn btn-gold btn-sm d-inline-flex align-items-center justify-content-center" href="{{ route('register') }}" title="Register" aria-label="Register">
                        <i class="bi bi-person-plus"></i>
                    </a>
                @else
                    @if($showCustomerNav)
                        @php($notificationCount = auth()->user()->unreadNotifications()->count())
                        @php($cartCount = (int) (auth()->user()->cart?->unique_item_count ?? 0))
                        <a class="nav-icon-link" href="{{ route('cart.index') }}" aria-label="Cart, {{ $cartCount }} {{ \Illuminate\Support\Str::plural('product', $cartCount) }}">
                            <i class="bi bi-bag"></i>
                            <span class="d-none d-md-inline">Cart</span>
                            <span class="cart-count {{ $cartCount > 0 ? '' : 'd-none' }}" data-cart-count aria-live="polite">{{ $cartCount }}</span>
                        </a>

                        <div class="dropdown nav-account" data-notification-url="{{ route('notifications.unread-count') }}">
                            <button class="nav-account-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open account menu">
                                @include('partials.avatar', ['user' => auth()->user(), 'size' => 'md'])
                                <span class="nav-account-name">
                                    <span class="nav-account-name-text">{{ \Illuminate\Support\Str::limit(auth()->user()->name, 14) }}</span><span class="nav-account-unread {{ $notificationCount > 0 ? '' : 'd-none' }}" data-notification-count-label>{{ $notificationCount > 0 ? ' ('.$notificationCount.')' : '' }}</span>
                                </span>
                                <i class="bi bi-chevron-down nav-account-chevron" aria-hidden="true"></i>
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end nav-account-menu">
                                <li class="nav-account-head">
                                    <span class="nav-account-head-name">{{ auth()->user()->name }}</span>
                                    <span class="nav-account-head-email">Manage your account and preferences</span>
                                </li>
                                <li>
                                    <a class="dropdown-item nav-account-item" href="{{ route('notifications.index') }}" aria-label="Notifications, {{ $notificationCount }} unread">
                                        <i class="bi bi-bell" aria-hidden="true"></i>
                                        <span class="flex-grow-1">Notifications</span>
                                        <span class="cart-count {{ $notificationCount > 0 ? '' : 'd-none' }}" data-notification-count aria-live="polite">{{ $notificationCount }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item nav-account-item" href="{{ route('profile.edit') }}">
                                        <i class="bi bi-person" aria-hidden="true"></i>
                                        <span>Profile</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider nav-account-divider"></li>
                                <li>
                                    <button type="button" class="dropdown-item nav-account-item" data-theme-toggle aria-label="Switch to light mode" aria-pressed="false">
                                        <i class="bi bi-sun theme-icon-when-dark" aria-hidden="true"></i>
                                        <i class="bi bi-moon-stars theme-icon-when-light" aria-hidden="true"></i>
                                        <span>Dark mode</span>
                                    </button>
                                </li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item nav-account-item nav-account-signout">
                                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                            <span>Sign out</span>
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        @include('partials.theme-toggle')
                        <a class="nav-icon-link" href="{{ route('profile.edit') }}" aria-label="Profile">
                            @include('partials.avatar', ['user' => auth()->user(), 'size' => 'md'])
                            <span class="d-none d-xl-inline">{{ \Illuminate\Support\Str::limit(auth()->user()->name, 14) }}</span>
                        </a>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="nav-icon-link border-0 bg-transparent" aria-label="Sign out" data-bs-toggle="tooltip" title="Sign out">
                                <i class="bi bi-box-arrow-right"></i>
                            </button>
                        </form>
                    @endif
                @endguest
            </div>
        </div>
    </div>
</nav>

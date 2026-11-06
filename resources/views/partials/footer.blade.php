@php
    $hasHours = filled($site['contact_hours_weekday'] ?? null) || filled($site['contact_hours_weekend'] ?? null);
    $hasVisit = filled($site['contact_address'] ?? null) || filled($site['contact_phone'] ?? null) || filled($site['contact_email'] ?? null);
@endphp
<footer class="site-footer mt-5">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-4">
                <a class="navbar-brand-bp mb-3" href="{{ route('home') }}">
                    <span class="brand-mark"><i class="bi bi-cup-hot-fill"></i></span>
                    <span>
                        <span class="brand-name">{{ mb_strtoupper($site['brand_name']) }}</span>
                        <span class="brand-tag">{{ $site['brand_tagline'] }}</span>
                    </span>
                </a>
                <p class="small mb-3" style="max-width: 19rem;">{{ $site['footer_intro'] }}</p>
                @if(filled($site['footer_primary_badge'] ?? null) || filled($site['footer_secondary_badge'] ?? null))
                    <div class="d-flex gap-2">
                        @if(filled($site['footer_primary_badge'] ?? null))
                            <span class="hero-badge"><span class="dot"></span>{{ $site['footer_primary_badge'] }}</span>
                        @endif
                        @if(filled($site['footer_secondary_badge'] ?? null))
                            <span class="hero-badge"><span class="dot"></span>{{ $site['footer_secondary_badge'] }}</span>
                        @endif
                    </div>
                @endif
            </div>

            <div class="col-6 col-lg-2">
                <div class="footer-title">Explore</div>
                <a class="footer-link" href="{{ route('home') }}">Home</a>
                @auth
                    <a class="footer-link" href="{{ route('menu.index') }}">Full menu</a>
                    <a class="footer-link" href="{{ route('orders.index') }}">My orders</a>
                    <a class="footer-link" href="{{ route('profile.edit') }}">Profile</a>
                @else
                    <a class="footer-link" href="{{ route('login') }}">Sign in</a>
                    <a class="footer-link" href="{{ route('register') }}">Create account</a>
                @endauth
            </div>

            @if($hasHours)
                <div class="col-6 col-lg-3">
                    <div class="footer-title">Hours</div>
                    @if(filled($site['contact_hours_weekday'] ?? null))
                        <p class="small mb-2">{{ $site['contact_hours_weekday_label'] }}<br><span class="text-cream">{{ $site['contact_hours_weekday'] }}</span></p>
                    @endif
                    @if(filled($site['contact_hours_weekend'] ?? null))
                        <p class="small mb-0">{{ $site['contact_hours_weekend_label'] }}<br><span class="text-cream">{{ $site['contact_hours_weekend'] }}</span></p>
                    @endif
                </div>
            @endif

            @if($hasVisit)
                <div class="col-lg-3">
                    <div class="footer-title">Visit</div>
                    @if(filled($site['contact_address'] ?? null))
                        <p class="small mb-2">{{ $site['contact_address'] }}</p>
                    @endif
                    @if(filled($site['contact_phone'] ?? null))
                        <p class="small mb-1"><a class="footer-link d-inline p-0" href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}">{{ $site['contact_phone'] }}</a></p>
                    @endif
                    @if(filled($site['contact_email'] ?? null))
                        <p class="small mb-0"><a class="footer-link d-inline p-0" href="mailto:{{ $site['contact_email'] }}">{{ $site['contact_email'] }}</a></p>
                    @endif
                </div>
            @endif
        </div>

        <div class="footer-meta d-flex flex-column flex-md-row justify-content-between gap-2">
            <span>&copy; {{ date('Y') }} {{ $site['brand_name'] }}. {{ $site['footer_copyright'] }}</span>
            <span>{{ $site['footer_service_note'] }}</span>
        </div>
    </div>
</footer>

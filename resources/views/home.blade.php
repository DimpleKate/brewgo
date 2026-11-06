@extends('layouts.app')

@php
    $hasHomeHours = filled($site['contact_hours_weekday'] ?? null) || filled($site['contact_hours_weekend'] ?? null);
    $hasHomeContactDetails = filled($site['contact_address'] ?? null) || filled($site['contact_phone'] ?? null) || filled($site['contact_email'] ?? null);
@endphp

@section('title', $site['home_meta_title'] ?? $site['brand_name'])

@section('content')
<section class="hero" id="home" @if(filled($site['home_hero_image_url'] ?? null)) style="--hero-image: url('{{ $site['home_hero_image_url'] }}');" @endif>
    <div class="container position-relative">
        <div class="hero-content">
            @if(filled($site['home_hero_badge'] ?? null))
                <span class="hero-badge"><span class="dot"></span>{{ $site['home_hero_badge'] }}</span>
            @endif
            <h1 class="hero-title">
                {{ $site['home_hero_title'] }}
                @if(filled($site['home_hero_emphasis'] ?? null))
                    <br><em>{{ $site['home_hero_emphasis'] }}</em>
                @endif
            </h1>
            @if(filled($site['home_hero_copy'] ?? null))
                <p class="hero-copy">{{ $site['home_hero_copy'] }}</p>
            @endif
            <div class="d-flex flex-wrap gap-2">
                @auth
                    <a class="btn btn-gold" href="{{ route('menu.index') }}">{{ $site['home_hero_primary_label'] }} <i class="bi bi-arrow-right ms-1"></i></a>
                    @if($carouselSlides->isNotEmpty())
                        <a class="btn btn-outline-gold" href="#signature">{{ $site['home_hero_secondary_label'] }}</a>
                    @endif
                @else
                    <a class="btn btn-gold" href="{{ route('register') }}">{{ $site['home_hero_primary_label'] }} <i class="bi bi-arrow-right ms-1"></i></a>
                @endauth
            </div>
            @if(filled($site['home_hero_meta_one'] ?? null) || filled($site['home_hero_meta_two'] ?? null) || filled($site['home_hero_meta_three'] ?? null))
                <div class="hero-meta">
                    @if(filled($site['home_hero_meta_one'] ?? null))
                        <span><i class="bi bi-cup-hot"></i> {{ $site['home_hero_meta_one'] }}</span>
                    @endif
                    @if(filled($site['home_hero_meta_two'] ?? null))
                        <span><i class="bi bi-bag-check"></i> {{ $site['home_hero_meta_two'] }}</span>
                    @endif
                    @if(filled($site['home_hero_meta_three'] ?? null))
                        <span><i class="bi bi-stars"></i> {{ $site['home_hero_meta_three'] }}</span>
                    @endif
                </div>
            @endif
        </div>

        @php($heroProduct = $featuredProducts->first())
        @if($heroProduct)
            <div class="hero-float-card d-none d-xl-block">
                <div class="small-label">{{ $site['home_hero_float_label'] }}</div>
                <h6>{{ $heroProduct->name }}</h6>
                <p>{{ $heroProduct->description }}</p>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    @if($heroProduct->requiresSizeSelection())
                        <span class="text-gold font-display fs-6">Choose size</span>
                    @else
                        <span class="text-gold font-display fs-5">₱{{ number_format((float) $heroProduct->base_price, 2) }}</span>
                    @endif
                    <a href="{{ route('menu.show', $heroProduct) }}" class="coverflow-cta" aria-label="View {{ $heroProduct->name }}"><i class="bi bi-arrow-up-right"></i></a>
                </div>
            </div>
        @endif
    </div>
</section>

@if($carouselSlides->isNotEmpty())
<section class="coverflow-section" id="signature" data-coverflow aria-roledescription="carousel" aria-label="Featured Brew &amp; Go moments">
    <div class="container">
        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
            <div>
                <span class="eyebrow">{{ $site['home_signature_eyebrow'] }}</span>
                <h2 class="section-title mb-0">{{ $site['home_signature_title'] }} <span class="gold-text">{{ $site['home_signature_accent'] }}</span></h2>
            </div>
            <div class="text-md-end">
                <div class="text-gold font-display fs-5" data-coverflow-count>{{ str_pad('1', 2, '0', STR_PAD_LEFT) }} / {{ str_pad((string) $carouselSlides->count(), 2, '0', STR_PAD_LEFT) }}</div>
                <div class="text-muted-bp small">{{ $site['home_signature_hint'] }}</div>
            </div>
        </div>

        <div class="coverflow">
            <button type="button" class="coverflow-nav prev" data-coverflow-prev aria-label="Previous slide">
                <i class="bi bi-arrow-left"></i>
            </button>
            <button type="button" class="coverflow-nav next" data-coverflow-next aria-label="Next slide">
                <i class="bi bi-arrow-right"></i>
            </button>

            <div class="coverflow-stage" data-coverflow-stage>
                @foreach($carouselSlides as $slide)
                    @php($hasCaption = filled($slide->title) || filled($slide->description) || filled($slide->link_url))
                    <div class="coverflow-slide {{ $loop->first ? 'is-active' : '' }}"
                         data-coverflow-slide
                         data-name="{{ $slide->title ?: 'Brew & Go featured moment' }}">
                        <article class="coverflow-card {{ $hasCaption ? '' : 'coverflow-card-image-only' }}">
                            <div class="coverflow-image">
                                @if($slide->image_url)
                                    <img src="{{ $slide->image_url }}" alt="{{ $slide->title ?: 'Brew & Go featured moment' }}" loading="lazy">
                                @else
                                    <div class="coverflow-image-fallback media-fallback"><i class="bi bi-image"></i></div>
                                @endif
                            </div>
                            @if($hasCaption)
                                <div class="coverflow-body">
                                    @if(filled($slide->title))
                                        <h3>{{ $slide->title }}</h3>
                                    @endif
                                    @if(filled($slide->description))
                                        <p>{{ $slide->description }}</p>
                                    @endif
                                    @if($slide->link_url)
                                        <a class="coverflow-footer coverflow-action"
                                           href="{{ $slide->link_url }}"
                                           @if(\Illuminate\Support\Str::startsWith($slide->link_url, ['http://', 'https://'])) target="_blank" rel="noopener noreferrer" @endif>
                                            <span>{{ $slide->link_label ?: 'Learn more' }}</span>
                                            <span class="coverflow-cta"><i class="bi bi-arrow-up-right"></i></span>
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </article>
                    </div>
                @endforeach
            </div>

            <div class="coverflow-dots" data-coverflow-dots aria-label="Carousel pagination"></div>
        </div>
    </div>
</section>
@endif

@if($promotions->isNotEmpty())
<section class="pb-5">
    <div class="container">
        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
            <div>
                <span class="eyebrow">{{ $site['home_promotions_eyebrow'] }}</span>
                <h2 class="section-title mb-0">{{ $site['home_promotions_title'] }} <span class="gold-text">{{ $site['home_promotions_accent'] }}</span></h2>
            </div>
            <p class="section-subtitle mb-0">{{ $site['home_promotions_copy'] }}</p>
        </div>

        <div class="row g-4">
            @foreach($promotions as $promotion)
                <div class="col-md-4">
                    <article class="promo-card">
                        @if($promotion->image_url)
                            <img src="{{ $promotion->image_url }}" alt="{{ $promotion->title }}" loading="lazy">
                        @else
                            <div class="promo-image-fallback"><i class="bi bi-ticket-perforated"></i></div>
                        @endif
                        <div class="promo-content">
                            @if($promotion->code)
                                <span class="code">{{ $promotion->code }}</span>
                            @endif
                            <h3>{{ $promotion->title }}</h3>
                            <p>{{ $promotion->description }}</p>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="section-pad pt-4" id="about">
    <div class="container">
        <div class="row g-4 g-lg-5 align-items-center mb-4 mb-lg-5">
            <div class="col-lg-7">
                <span class="eyebrow">{{ $site['home_about_eyebrow'] }}</span>
                <h2 class="section-title">{{ $site['home_about_title'] }} <span class="gold-text">{{ $site['home_about_accent'] }}</span></h2>
                <p class="section-subtitle mb-0">{{ $site['home_about_intro'] }}</p>
            </div>
            <div class="col-lg-5">
                @if(filled($site['home_about_body_one'] ?? null))
                    <p class="text-muted-bp">{{ $site['home_about_body_one'] }}</p>
                @endif
                @if(filled($site['home_about_body_two'] ?? null))
                    <p class="text-muted-bp mb-0">{{ $site['home_about_body_two'] }}</p>
                @endif
            </div>
        </div>

        <div class="row g-3 g-lg-4">
            @if(filled($site['home_about_feature_one_title'] ?? null) || filled($site['home_about_feature_one_text'] ?? null))
                <div class="col-md-4">
                    <div class="feature-tile">
                        <div class="feature-icon"><i class="bi bi-fire"></i></div>
                        <h4>{{ $site['home_about_feature_one_title'] }}</h4>
                        <p>{{ $site['home_about_feature_one_text'] }}</p>
                    </div>
                </div>
            @endif
            @if(filled($site['home_about_feature_two_title'] ?? null) || filled($site['home_about_feature_two_text'] ?? null))
                <div class="col-md-4">
                    <div class="feature-tile">
                        <div class="feature-icon"><i class="bi bi-sliders"></i></div>
                        <h4>{{ $site['home_about_feature_two_title'] }}</h4>
                        <p>{{ $site['home_about_feature_two_text'] }}</p>
                    </div>
                </div>
            @endif
            @if(filled($site['home_about_feature_three_title'] ?? null) || filled($site['home_about_feature_three_text'] ?? null))
                <div class="col-md-4">
                    <div class="feature-tile">
                        <div class="feature-icon"><i class="bi bi-bag-heart"></i></div>
                        <h4>{{ $site['home_about_feature_three_title'] }}</h4>
                        <p>{{ $site['home_about_feature_three_text'] }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

<section class="section-pad pt-4" id="contact">
    <div class="container">
        <div class="row align-items-end justify-content-between gap-3 mb-4">
            <div class="col-lg-7">
                <span class="eyebrow">{{ $site['home_contact_eyebrow'] }}</span>
                <h2 class="section-title mb-0">{{ $site['home_contact_title'] }} <span class="gold-text">{{ $site['home_contact_accent'] }}</span></h2>
                <p class="section-subtitle mb-0">{{ $site['home_contact_intro'] }}</p>
            </div>
            @if(filled($site['contact_email'] ?? null))
                <div class="col-lg-auto">
                    <a class="btn btn-outline-gold" href="mailto:{{ $site['contact_email'] }}">{{ $site['home_contact_email_button'] }} <i class="bi bi-arrow-up-right ms-1"></i></a>
                </div>
            @endif
        </div>

        @if($hasHomeContactDetails || $hasHomeHours)
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="row g-3">
                        @if(filled($site['contact_address'] ?? null))
                            <div class="col-md-6">
                                <div class="feature-tile h-100">
                                    <div class="feature-icon"><i class="bi bi-geo-alt"></i></div>
                                    <h4>{{ $site['contact_visit_title'] }}</h4>
                                    <p class="mb-3">{{ $site['contact_address'] }}</p>
                                    @if(filled($site['contact_visit_note'] ?? null))
                                        <span class="small text-muted-bp">{{ $site['contact_visit_note'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if(filled($site['contact_phone'] ?? null))
                            <div class="col-md-6">
                                <div class="feature-tile h-100">
                                    <div class="feature-icon"><i class="bi bi-telephone"></i></div>
                                    <h4>{{ $site['contact_call_title'] }}</h4>
                                    <p class="mb-3"><a class="text-gold text-decoration-none" href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}">{{ $site['contact_phone'] }}</a></p>
                                    @if(filled($site['contact_call_note'] ?? null))
                                        <span class="small text-muted-bp">{{ $site['contact_call_note'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if(filled($site['contact_email'] ?? null))
                            <div class="col-md-6">
                                <div class="feature-tile h-100">
                                    <div class="feature-icon"><i class="bi bi-envelope"></i></div>
                                    <h4>{{ $site['contact_email_title'] }}</h4>
                                    <p class="mb-3"><a class="text-gold text-decoration-none" href="mailto:{{ $site['contact_email'] }}">{{ $site['contact_email'] }}</a></p>
                                    @if(filled($site['contact_email_note'] ?? null))
                                        <span class="small text-muted-bp">{{ $site['contact_email_note'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <div class="feature-tile h-100">
                                <div class="feature-icon"><i class="bi bi-cup-hot"></i></div>
                                <h4>{{ $site['contact_support_title'] }}</h4>
                                <p class="mb-3">{{ $site['contact_support_copy'] }}</p>
                                <a class="small text-gold text-decoration-none" href="{{ route('orders.index') }}">{{ $site['contact_support_link_label'] }} <i class="bi bi-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                @if($hasHomeHours)
                    <div class="col-lg-5">
                        <div class="feature-tile h-100">
                            <div class="feature-icon"><i class="bi bi-clock"></i></div>
                            <h4>{{ $site['contact_hours_title'] }}</h4>
                            @if(filled($site['contact_hours_weekday'] ?? null))
                                <div class="d-flex justify-content-between gap-3 py-2 border-bottom border-secondary-subtle">
                                    <span class="text-muted-bp">{{ $site['contact_hours_weekday_label'] }}</span>
                                    <span class="text-cream">{{ $site['contact_hours_weekday'] }}</span>
                                </div>
                            @endif
                            @if(filled($site['contact_hours_weekend'] ?? null))
                                <div class="d-flex justify-content-between gap-3 py-2 border-bottom border-secondary-subtle">
                                    <span class="text-muted-bp">{{ $site['contact_hours_weekend_label'] }}</span>
                                    <span class="text-cream">{{ $site['contact_hours_weekend'] }}</span>
                                </div>
                            @endif
                            @if(filled($site['contact_hours_note'] ?? null))
                                <p class="small text-muted-bp mt-3 mb-0">{{ $site['contact_hours_note'] }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="empty-state">
                <i class="bi bi-envelope"></i>
                <p class="mb-0">{{ $site['contact_empty_note'] }}</p>
            </div>
        @endif

        <div class="cta-panel mt-4 mt-lg-5">
            <div class="row align-items-center g-4 position-relative" style="z-index: 2;">
                <div class="col-lg-8">
                    <span class="eyebrow">{{ $site['home_cta_eyebrow'] }}</span>
                    <h2 class="section-title mb-2">{{ $site['home_cta_title'] }}<br><span class="gold-text">{{ $site['home_cta_accent'] }}</span></h2>
                    <p class="mb-0">{{ $site['home_cta_copy'] }}</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    @auth
                        <a class="btn btn-gold" href="{{ route('menu.index') }}">{{ $site['home_cta_auth_label'] }} <i class="bi bi-arrow-right ms-1"></i></a>
                    @else
                        <a class="btn btn-gold" href="{{ route('register') }}">{{ $site['home_cta_guest_label'] }} <i class="bi bi-arrow-right ms-1"></i></a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('js/coverflow.js') }}"></script>
@endpush

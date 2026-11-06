@extends('layouts.app')

@php
    $hasContactHours = filled($site['contact_hours_weekday'] ?? null) || filled($site['contact_hours_weekend'] ?? null);
    $hasContactDetails = filled($site['contact_address'] ?? null) || filled($site['contact_phone'] ?? null) || filled($site['contact_email'] ?? null);
@endphp

@section('title', $site['contact_meta_title'] ?? $site['brand_name'])

@section('content')
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">{{ $site['contact_hero_eyebrow'] }}</span>
        <h1>{{ $site['contact_hero_title'] }} <span class="gold-text">{{ $site['contact_hero_accent'] }}</span></h1>
        <p class="section-subtitle mb-0">{{ $site['contact_hero_intro'] }}</p>
    </div>
</section>

<section class="section-pad pt-4">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7">
                <span class="eyebrow">{{ $site['contact_intro_eyebrow'] }}</span>
                <h2 class="section-title">{{ $site['contact_intro_title'] }} <span class="gold-text">{{ $site['contact_intro_accent'] }}</span></h2>

                @if($hasContactDetails)
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
                @else
                    <div class="empty-state">
                        <i class="bi bi-envelope"></i>
                        <p class="mb-0">{{ $site['contact_empty_note'] }}</p>
                    </div>
                @endif
            </div>

            @if($hasContactHours)
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

        <div class="cta-panel mt-4 mt-lg-5">
            <div class="row align-items-center g-4 position-relative" style="z-index: 2;">
                <div class="col-lg-8">
                    <span class="eyebrow">{{ $site['contact_cta_eyebrow'] }}</span>
                    <h2 class="section-title mb-2">{{ $site['contact_cta_title'] }} <span class="gold-text">{{ $site['contact_cta_accent'] }}</span></h2>
                    <p class="mb-0">{{ $site['contact_cta_copy'] }}</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a class="btn btn-gold" href="{{ route('menu.index') }}">{{ $site['contact_cta_label'] }} <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@extends('layouts.app')

@section('title', $site['about_meta_title'] ?? $site['brand_name'])

@section('content')
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">{{ $site['about_hero_eyebrow'] }}</span>
        <h1>{{ $site['about_hero_title'] }} <span class="gold-text">{{ $site['about_hero_accent'] }}</span></h1>
        <p class="section-subtitle mb-0">{{ $site['about_hero_intro'] }}</p>
    </div>
</section>

<section class="section-pad pt-4">
    <div class="container">
        <div class="row g-4 g-lg-5 align-items-center">
            <div class="col-lg-6">
                <span class="eyebrow">{{ $site['about_story_eyebrow'] }}</span>
                <h2 class="section-title">{{ $site['about_story_title'] }} <span class="gold-text">{{ $site['about_story_accent'] }}</span></h2>
                @if(filled($site['about_story_body_one'] ?? null))
                    <p class="text-muted-bp">{{ $site['about_story_body_one'] }}</p>
                @endif
                @if(filled($site['about_story_body_two'] ?? null))
                    <p class="text-muted-bp mb-0">{{ $site['about_story_body_two'] }}</p>
                @endif
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    @if(filled($site['about_feature_one_title'] ?? null) || filled($site['about_feature_one_text'] ?? null))
                        <div class="col-sm-6">
                            <div class="feature-tile h-100">
                                <div class="feature-icon"><i class="bi bi-fire"></i></div>
                                <h4>{{ $site['about_feature_one_title'] }}</h4>
                                <p>{{ $site['about_feature_one_text'] }}</p>
                            </div>
                        </div>
                    @endif
                    @if(filled($site['about_feature_two_title'] ?? null) || filled($site['about_feature_two_text'] ?? null))
                        <div class="col-sm-6">
                            <div class="feature-tile h-100">
                                <div class="feature-icon"><i class="bi bi-sliders"></i></div>
                                <h4>{{ $site['about_feature_two_title'] }}</h4>
                                <p>{{ $site['about_feature_two_text'] }}</p>
                            </div>
                        </div>
                    @endif
                    @if(filled($site['about_feature_three_title'] ?? null) || filled($site['about_feature_three_text'] ?? null))
                        <div class="col-sm-6">
                            <div class="feature-tile h-100">
                                <div class="feature-icon"><i class="bi bi-bag-heart"></i></div>
                                <h4>{{ $site['about_feature_three_title'] }}</h4>
                                <p>{{ $site['about_feature_three_text'] }}</p>
                            </div>
                        </div>
                    @endif
                    @if(filled($site['about_feature_four_title'] ?? null) || filled($site['about_feature_four_text'] ?? null))
                        <div class="col-sm-6">
                            <div class="feature-tile h-100">
                                <div class="feature-icon"><i class="bi bi-people"></i></div>
                                <h4>{{ $site['about_feature_four_title'] }}</h4>
                                <p>{{ $site['about_feature_four_text'] }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="cta-panel mt-4 mt-lg-5">
            <div class="row align-items-center g-4 position-relative" style="z-index: 2;">
                <div class="col-lg-8">
                    <span class="eyebrow">{{ $site['about_cta_eyebrow'] }}</span>
                    <h2 class="section-title mb-2">{{ $site['about_cta_title'] }} <span class="gold-text">{{ $site['about_cta_accent'] }}</span></h2>
                    <p class="mb-0">{{ $site['about_cta_copy'] }}</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a class="btn btn-gold" href="{{ route('menu.index') }}">{{ $site['about_cta_label'] }} <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

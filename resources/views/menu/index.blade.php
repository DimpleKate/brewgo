@extends('layouts.app')

@section('title', $site['menu_meta_title'] ?? $site['brand_name'])

@section('content')
@php
    $categoryUrl = fn (?string $slug) => route('menu.index', array_filter([
        'category' => $slug,
        'search' => $search !== '' ? $search : null,
    ]));
@endphp
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">{{ $site['menu_hero_eyebrow'] }}</span>
        <h1>{{ $site['menu_hero_title'] }} <span class="gold-text">{{ $site['menu_hero_accent'] }}</span></h1>
        <p class="section-subtitle mb-0">{{ $site['menu_hero_intro'] }}</p>
    </div>
</section>

<section class="py-4 py-lg-5">
    <div class="container" data-product-filter>
        <div class="menu-category-chips" role="group" aria-label="Filter the menu by category">
            <a class="menu-category-chip{{ $activeCategory === null ? ' is-active' : '' }}"
               href="{{ $categoryUrl(null) }}"
               data-menu-category=""
               @if($activeCategory === null) aria-current="page" @endif>All</a>
            @foreach($categories as $category)
                <a class="menu-category-chip{{ $activeCategory?->is($category) ? ' is-active' : '' }}"
                   href="{{ $categoryUrl($category->slug) }}"
                   data-menu-category="{{ $category->slug }}"
                   @if($activeCategory?->is($category)) aria-current="page" @endif>{{ $category->name }}</a>
            @endforeach
        </div>

        @include('partials.product-filters', [
            'search' => $search,
            'category' => $activeCategory?->slug,
            'inputId' => 'menuSearch',
            'label' => 'Search the menu',
            'placeholder' => 'Search by name, description, or category',
        ])

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="text-muted-bp small" data-product-count aria-live="polite">
                @if($products->isNotEmpty())
                    Showing 1–{{ $products->count() }} of {{ $products->count() }} {{ \Illuminate\Support\Str::plural('item', $products->count()) }}
                @else
                    No items
                @endif
            </div>
        </div>

        <div class="row g-2 g-md-3">
            @foreach($products as $product)
                <div class="col-sm-6 col-md-4 col-lg-3 col-xxl-2"
                     data-product-item
                     data-product-category="{{ $product->category?->slug }}"
                     data-product-search-text="{{ \Illuminate\Support\Str::lower($product->name.' '.$product->description.' '.($product->category?->name ?? '')) }}">
                    @include('partials.product-card', ['product' => $product])
                </div>
            @endforeach
        </div>

        <div class="empty-state {{ $products->isNotEmpty() ? 'd-none' : '' }}" data-product-empty>
            <i class="bi {{ $products->isNotEmpty() ? 'bi-search' : 'bi-cup-hot' }}"></i>
            @if($products->isNotEmpty())
                <h2 class="section-title fs-3">No products found.</h2>
                <p class="mb-3">Try a different keyword or category.</p>
                <button type="button" class="btn btn-gold" data-product-reset>Clear filters</button>
            @else
                <h2 class="section-title fs-3">{{ $site['home_menu_empty_title'] }}</h2>
                <p class="mb-0">{{ $site['home_menu_empty_copy'] }}</p>
            @endif
        </div>
    </div>
</section>
@endsection

@extends('layouts.app')

@section('title', $product->name.' | '.$site['brand_name'])

@section('content')
@php
    $requiresSizeSelection = $optionGroups->get('size', collect())->isNotEmpty();
    $soldOut = ! $product->isOrderable();
    $defaultSelectedOptions = $optionGroups
        ->map(fn ($options) => $options->firstWhere('is_default', true) ?? $options->first())
        ->filter();
    $previewUnitPrice = $requiresSizeSelection ? null : (float) $product->base_price;
    $previewUnitPrice += (float) $defaultSelectedOptions
        ->reject(fn ($option) => $option->group_name === 'size')
        ->sum('price_adjustment');
@endphp

<section class="py-4 py-lg-5">
    <div class="container">
        <div class="row g-4 g-lg-5 align-items-start">
            <div class="col-lg-6">
                <div class="product-visual">
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                    @else
                        <div class="media-fallback product-visual-fallback"><i class="bi bi-cup-hot"></i></div>
                    @endif
                </div>
            </div>

            <div class="col-lg-6">
                <div class="product-category">{{ $product->category?->name }}</div>
                <h1 class="section-title mt-2">{{ $product->name }}</h1>
                <p class="mb-3">{{ $product->description }}</p>
                @if($soldOut)
                    <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                        <span>This item is currently sold out. Please check back later or choose another item.</span>
                    </div>
                @endif
                @if($reviewCount > 0)
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        @include('partials.star-rating', ['rating' => $reviewAverage])
                        <span class="small text-muted-bp">{{ number_format($reviewAverage, 1) }} out of 5 · {{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }}</span>
                        <a class="small text-gold" href="#reviews">Read reviews</a>
                    </div>
                @endif
                @unless($requiresSizeSelection)
                    <div class="d-flex align-items-baseline gap-2 mb-4">
                        <span class="price-large">₱{{ number_format((float) $product->base_price, 2) }}</span>
                    </div>
                @endunless

                @auth
                    <form action="{{ route('cart.items.store') }}" method="POST" data-customizer data-base-price="{{ (float) $product->base_price }}" data-requires-size="{{ $requiresSizeSelection ? '1' : '0' }}">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        @forelse($optionGroups as $group => $options)
                            @php
                                $defaultOption = $options->firstWhere('is_default', true) ?? $options->first();
                                $groupLabel = \App\Models\ProductOption::GROUPS[$group] ?? ucfirst($group);
                                $isSizeGroup = $group === 'size';
                            @endphp
                            <div class="option-group" @if($isSizeGroup) data-size-group @endif>
                                <div class="option-group-title">
                                    <span>{{ $isSizeGroup ? 'Choose your size' : $groupLabel }}</span>
                                    <span>{{ $isSizeGroup ? 'Required' : 'Choose one' }}</span>
                                </div>
                                <div class="choice-row">
                                    @foreach($options as $option)
                                        @php($absolutePrice = $isSizeGroup && $option->price !== null ? (float) $option->price : null)
                                        <label class="choice-card">
                                            <input type="radio"
                                                   class="customizer-option"
                                                   name="options[{{ $group }}]"
                                                   value="{{ $option->id }}"
                                                   data-price="{{ $absolutePrice ?? (float) $option->price_adjustment }}"
                                                   data-price-mode="{{ $absolutePrice !== null ? 'absolute' : 'adjustment' }}"
                                                   @if($isSizeGroup) data-size-option @endif
                                                   @checked(! $isSizeGroup && $defaultOption?->id === $option->id)
                                                   @required($isSizeGroup)>
                                            <span class="choice-body">
                                                {{ $option->name }}
                                                @if($absolutePrice !== null)
                                                    <small>₱{{ number_format($absolutePrice, 2) }}</small>
                                                @elseif((float) $option->price_adjustment > 0)
                                                    <small>+₱{{ number_format((float) $option->price_adjustment, 2) }}</small>
                                                @elseif((float) $option->price_adjustment < 0)
                                                    <small>-₱{{ number_format(abs((float) $option->price_adjustment), 2) }}</small>
                                                @else
                                                    <small>included</small>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @if($isSizeGroup)
                                    <p class="size-select-prompt" data-size-prompt role="status">
                                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i> Select a size to continue.
                                    </p>
                                @endif
                            </div>
                        @empty
                            <div class="option-group">
                                <p class="mb-0 small">This item is prepared exactly as described. No customizations available.</p>
                            </div>
                        @endforelse

                        @if($product->addons->isNotEmpty())
                            <div class="option-group">
                                <div class="option-group-title">
                                    <span>Add-ons</span>
                                    <span>Optional · multiple</span>
                                </div>
                                <div class="choice-row">
                                    @foreach($product->addons as $addon)
                                        <label class="choice-card addon-card">
                                            <input type="checkbox"
                                                   class="customizer-addon"
                                                   name="addons[]"
                                                   value="{{ $addon->id }}"
                                                   data-price="{{ (float) $addon->price }}">
                                            <span class="choice-body">
                                                {{ $addon->name }}
                                                <small>+₱{{ number_format((float) $addon->price, 2) }}</small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="option-group">
                            <div class="option-group-title">
                                <span>Special instructions</span>
                                <span>Optional · per item</span>
                            </div>
                            <label class="form-label visually-hidden" for="itemNote">Special instructions</label>
                            <textarea id="itemNote"
                                      name="note"
                                      class="form-control @error('note') is-invalid @enderror"
                                      rows="2"
                                      maxlength="255"
                                      placeholder="Less ice, no sugar, extra sweet, no whipped cream">{{ old('note') }}</textarea>
                            @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">We'll pass this to the kitchen with this item. Keep it under 255 characters.</div>
                        </div>

                        <div class="option-group">
                            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
                                <div>
                                    <div class="form-label mb-2">Quantity</div>
                                    <div class="qty-control" data-qty>
                                        <button type="button" data-qty-minus aria-label="Decrease quantity"><i class="bi bi-dash"></i></button>
                                        <input type="text" name="quantity" value="1" inputmode="numeric" pattern="[0-9]*" min="1" max="20" data-customizer-qty aria-label="Quantity" required>
                                        <button type="button" data-qty-plus aria-label="Increase quantity"><i class="bi bi-plus"></i></button>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted-bp small">Unit price</div>
                                    <div class="fs-5 text-cream" data-customizer-unit>{{ $requiresSizeSelection ? 'Select a size' : '₱'.number_format($previewUnitPrice, 2) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="customizer-summary d-flex flex-wrap align-items-center justify-content-between gap-3 mt-2">
                            <div>
                                <div class="text-muted-bp small">Total</div>
                                <div class="customizer-total" data-customizer-total>{{ $requiresSizeSelection ? 'Select a size' : '₱'.number_format($previewUnitPrice, 2) }}</div>
                            </div>
                            <button class="btn btn-gold" type="submit" data-add-to-cart @disabled($requiresSizeSelection || $soldOut)>Add to Cart <i class="bi bi-bag-plus ms-1"></i></button>
                        </div>

                        <p class="small text-muted-bp mt-3 mb-0"><i class="bi bi-shield-check text-gold me-1"></i>Final prices are recalculated on the server at checkout.</p>
                    </form>
                @else
                    <div class="panel panel-pad">
                        <div class="feature-icon"><i class="bi bi-person-lock"></i></div>
                        <h3 class="fs-4">Sign in to customize and order.</h3>
                        <p class="small mb-3">Your cart, order history, and support requests are tied to your account.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-gold" href="{{ route('login') }}">Sign in</a>
                            <a class="btn btn-outline-gold" href="{{ route('register') }}">Create account</a>
                        </div>
                    </div>
                @endauth
            </div>
        </div>

        <section class="product-reviews mt-5" id="reviews" aria-labelledby="productReviewsTitle">
            <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-2 mb-3">
                <div>
                    <span class="eyebrow">Customer feedback</span>
                    <h2 class="section-title fs-3 mb-0" id="productReviewsTitle">Reviews</h2>
                </div>
                @if($reviewCount > 0)
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @include('partials.star-rating', ['rating' => $reviewAverage])
                        <span class="small text-muted-bp">{{ number_format($reviewAverage, 1) }} out of 5 · {{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }}</span>
                    </div>
                @endif
            </div>

            @if($reviews->isEmpty())
                <div class="panel panel-pad review-empty">
                    <i class="bi bi-chat-square-heart" aria-hidden="true"></i>
                    <div>
                        <div class="text-cream fw-semibold">No published reviews yet.</div>
                        <p class="small text-muted-bp mb-0">Reviews appear here once a verified customer has completed an order and the feedback has been approved.</p>
                    </div>
                </div>
            @else
                <div class="row g-3">
                    @foreach($reviews as $review)
                        <div class="col-md-6">
                            <article class="panel panel-pad h-100 review-card">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                    <div>
                                        <div class="text-cream fw-semibold">{{ $review->reviewerDisplayName() }}</div>
                                        <div class="timeline-meta">{{ $review->created_at->format('M d, Y') }}</div>
                                    </div>
                                    @include('partials.star-rating', ['rating' => $review->rating])
                                </div>
                                @if($review->comment)
                                    <p class="mb-2">{{ $review->comment }}</p>
                                @endif
                                <span class="review-verified"><i class="bi bi-patch-check-fill me-1" aria-hidden="true"></i>Verified purchase</span>
                            </article>
                        </div>
                    @endforeach
                </div>
                @if($reviewCount > $reviews->count())
                    <p class="small text-muted-bp mt-3 mb-0">Showing the {{ $reviews->count() }} most recent of {{ $reviewCount }} reviews.</p>
                @endif
            @endif
        </section>
    </div>
</section>
@endsection

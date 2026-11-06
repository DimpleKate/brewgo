@extends('layouts.app')

@section('title', 'Your Cart | '.$site['brand_name'])

@section('content')
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Almost there</span>
        <h1>Your <span class="gold-text">order bag.</span></h1>
        <p class="section-subtitle mb-0">Review your picks, adjust quantities, and continue to checkout.</p>
    </div>
</section>

<section class="section-pad pt-4">
    <div class="container">
        @if($cart->items->isEmpty())
            <div class="empty-state cart-empty">
                <span class="cart-empty-icon"><i class="bi bi-bag" aria-hidden="true"></i></span>
                <h2 class="section-title fs-3">Your bag is empty.</h2>
                <p class="cart-empty-copy">A good cup is only a few clicks away.</p>
                <a class="btn btn-gold cart-empty-cta" href="{{ route('menu.index') }}">
                    <span>Browse the menu</span>
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        @else
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="panel panel-pad">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="footer-title mb-0">Items</div>
                            <span class="text-muted-bp small" data-cart-total-quantity>{{ $cart->unique_item_count }} {{ \Illuminate\Support\Str::plural('product', $cart->unique_item_count) }}</span>
                        </div>

                        @foreach($cart->items as $item)
                            @php
                                $selectedOptions = $item->product->options->whereIn('id', $item->options ?? [])->sortBy('group_name');
                                $selectedAddons = $item->product->addons->whereIn('id', $item->addons ?? []);
                                $itemSoldOut = ! $item->product->isOrderable() || ! $item->product->hasStockFor($item->quantity);
                            @endphp
                            <div class="cart-item {{ $item->is_selected ? '' : 'is-unselected' }}" data-cart-item="{{ $item->id }}" data-product-id="{{ $item->product_id }}" data-quantity="{{ $item->quantity }}" data-unit-price="{{ (float) $item->unit_price }}" data-line-total="{{ $item->line_total }}" data-selected="{{ $item->is_selected ? '1' : '0' }}">
                                <div class="cart-select">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="cart-select-{{ $item->id }}"
                                           data-cart-select
                                           data-selection-url="{{ route('cart.items.selection', $item) }}"
                                           aria-label="Select {{ $item->product->name }} for checkout"
                                           @checked($item->is_selected)>
                                </div>

                                @if($item->product->image_url)
                                    <img class="cart-thumb" src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}">
                                @else
                                    <div class="cart-thumb-fallback media-fallback"><i class="bi bi-cup-hot"></i></div>
                                @endif

                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                        <div>
                                            <h3 class="cart-item-name">{{ $item->product->name }}</h3>
                                            <div class="product-category">{{ $item->product->category?->name }}</div>
                                            @if($itemSoldOut)
                                                <div class="small text-danger mt-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Sold out or not enough stock — adjust or remove this item before checkout.</div>
                                            @endif
                                        </div>
                                        <div class="cart-price" data-cart-line-total>₱{{ number_format($item->line_total, 2) }}</div>
                                    </div>

                                    <div class="cart-options">
                                        @foreach($selectedOptions as $option)
                                            @php($absolutePrice = $option->group_name === 'size' && $option->price !== null ? (float) $option->price : null)
                                            <span>{{ $option->name }}@if($absolutePrice !== null) (₱{{ number_format($absolutePrice, 2) }})@elseif((float) $option->price_adjustment !== 0.0) ({{ (float) $option->price_adjustment > 0 ? '+' : '-' }}₱{{ number_format(abs((float) $option->price_adjustment), 2) }})@endif</span>{{ ! $loop->last ? ' · ' : '' }}
                                        @endforeach
                                        @if($selectedAddons->isNotEmpty())
                                            @if($selectedOptions->isNotEmpty())<br>@endif
                                            <span class="text-gold">Add-ons:</span>
                                            @foreach($selectedAddons as $addon)
                                                <span>{{ $addon->name }} (+₱{{ number_format((float) $addon->price, 2) }})</span>{{ ! $loop->last ? ' · ' : '' }}
                                            @endforeach
                                        @endif
                                    </div>

                                    @if($item->note)
                                        <div class="small text-muted-bp mt-2">
                                            <i class="bi bi-chat-left-text text-gold me-1"></i>
                                            <span class="text-gold">Special instructions:</span> {{ $item->note }}
                                        </div>
                                    @endif

                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
                                        <form action="{{ route('cart.items.update', $item) }}" method="POST" class="d-flex flex-wrap align-items-center gap-2" data-cart-quantity-form>
                                            @csrf
                                            @method('PATCH')
                                            @foreach($item->options ?? [] as $optionId)
                                                <input type="hidden" name="options[]" value="{{ $optionId }}">
                                            @endforeach
                                            @foreach($item->addons ?? [] as $addonId)
                                                <input type="hidden" name="addons[]" value="{{ $addonId }}">
                                            @endforeach
                                            <div class="qty-control">
                                                <button type="button" data-cart-decrement aria-label="Decrease quantity for {{ $item->product->name }}" @disabled($item->quantity <= 1)><i class="bi bi-dash"></i></button>
                                                <input type="text" name="quantity" value="{{ $item->quantity }}" inputmode="numeric" pattern="[0-9]*" min="1" max="20" data-cart-quantity-input aria-label="Quantity for {{ $item->product->name }}" required>
                                                <button type="button" data-cart-increment aria-label="Increase quantity for {{ $item->product->name }}" @disabled($item->quantity >= 20)><i class="bi bi-plus"></i></button>
                                            </div>
                                            <div class="small text-danger w-100 d-none" data-cart-quantity-error role="alert"></div>
                                        </form>

                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-muted-bp small">₱{{ number_format((float) $item->unit_price, 2) }} each</span>
                                            <form action="{{ route('cart.items.destroy', $item) }}" method="POST" data-confirm="Remove this item from your cart?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-link btn-sm text-danger p-0 d-inline-flex align-items-center gap-1" type="submit" aria-label="Remove {{ $item->product->name }}">
                                                    <i class="bi bi-trash3"></i> Remove
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="small text-danger mt-2 d-none" data-cart-selection-error role="alert"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3">
                        <a class="btn btn-outline-gold btn-sm" href="{{ route('menu.index') }}"><i class="bi bi-arrow-left me-1"></i> Keep browsing</a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <aside class="panel panel-pad sticky-summary">
                        <div class="footer-title">Order summary</div>
                        <div class="summary-row">
                            <span>Selected items</span>
                            <span class="text-cream" data-cart-selected-summary>{{ $cart->selected_total_quantity }} of {{ $cart->total_quantity }} {{ \Illuminate\Support\Str::plural('item', $cart->total_quantity) }}</span>
                        </div>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span class="text-cream" data-cart-subtotal>₱{{ number_format($cart->subtotal, 2) }}</span>
                        </div>
                        <div class="summary-row">
                            <span>Service</span>
                            <span class="text-cream">Dine-in or pickup</span>
                        </div>
                        <div class="summary-row">
                            <span>Promotion</span>
                            <span class="text-cream">Applied at checkout</span>
                        </div>
                        <div class="summary-total">
                            <span>Estimated total</span>
                            <span class="text-gold" data-cart-total>₱{{ number_format($cart->subtotal, 2) }}</span>
                        </div>
                        <p class="small text-muted-bp mt-2 mb-2">Final pricing, add-ons, and any valid promotion are recalculated securely on the server.</p>
                        <p class="small text-muted-bp mb-3" data-cart-selection-hint>{{ $cart->has_selected_items ? 'Unselected items stay in your bag for later.' : 'Select at least one item to checkout.' }}</p>
                        <form action="{{ route('checkout.create') }}" method="GET" class="d-grid">
                            <button class="btn btn-gold w-100" type="submit" data-checkout-button @disabled(! $cart->has_selected_items)>
                                Continue to checkout <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </form>
                        <div class="text-center mt-3">
                            <span class="small text-muted-bp"><i class="bi bi-shield-check text-gold me-1"></i>No online payment required</span>
                        </div>
                    </aside>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection

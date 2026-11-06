@php
    $image = $product->image_url;
    $requiresSizeSelection = $product->requiresSizeSelection();
    $soldOut = ! $product->isOrderable();
@endphp
<div class="product-card h-100">
    <div class="product-image">
        @if($image)
            <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy">
        @else
            <div class="product-image-fallback media-fallback"><i class="bi bi-cup-hot"></i></div>
        @endif
        @if($soldOut)
            <span class="product-badge badge text-bg-danger">Sold out</span>
        @elseif($product->is_featured)
            <span class="product-badge">Signature</span>
        @endif
    </div>
    <div class="product-body">
        <div class="product-category">{{ $product->category?->name }}</div>
        <h3 class="product-name"><a href="{{ route('menu.show', $product) }}">{{ $product->name }}</a></h3>
        <p class="product-description">{{ $product->description }}</p>
        <div class="product-footer">
            @if($requiresSizeSelection)
                <div class="product-price product-price-pending">Select size</div>
            @else
                <div class="product-price">₱{{ number_format((float) $product->base_price, 2) }}</div>
            @endif
            @auth
                @if($soldOut)
                    <button class="btn btn-soft btn-sm" type="button" disabled>Sold out</button>
                @elseif($requiresSizeSelection)
                    <a class="btn btn-soft btn-sm" href="{{ route('menu.show', $product) }}">
                        Choose size <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                @else
                    <form action="{{ route('cart.items.store') }}" method="POST" class="product-card-cart" data-quick-add>
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="quick_add" value="1">
                        <button class="btn btn-soft btn-sm" type="submit" data-quick-add-button>
                            Add to Cart <i class="bi bi-bag-plus ms-1"></i>
                        </button>
                        <div class="product-card-cart-status small text-danger d-none" data-quick-add-status role="status"></div>
                    </form>
                @endif
            @endauth
        </div>
    </div>
</div>

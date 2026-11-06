@extends('layouts.app')

@section('title', 'My Orders | '.$site['brand_name'])

@section('content')
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">From our kitchen to you</span>
        <h1>Your <span class="gold-text">order history.</span></h1>
        <p class="section-subtitle mb-0">Track current orders, revisit past favorites, or request help.</p>
    </div>
</section>

<section class="section-pad pt-4">
    <div class="container">
        <div class="row g-3 align-items-end mb-4">
            <div class="col-lg-8">
                <form method="GET" action="{{ route('orders.index') }}" class="row g-2 align-items-end" data-live-filter-form>
                    <div class="col-sm-6 col-lg-5">
                        <label class="form-label" for="orderSearch">Search by order number</label>
                        <div class="search-bp">
                            <i class="bi bi-search"></i>
                            <input id="orderSearch" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="BG-...">
                        </div>
                    </div>
                    <div class="col-sm-4 col-lg-3">
                        <label class="form-label" for="statusFilter">Status</label>
                        <select id="statusFilter" name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach(\App\Enums\OrderStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(request()->filled('search') || request()->filled('status'))
                        <div class="col-auto">
                            <a class="btn btn-soft" href="{{ route('orders.index') }}">Reset</a>
                        </div>
                    @endif
                </form>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a class="btn btn-outline-gold" href="{{ route('menu.index') }}">Order again <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
        </div>

        @if($orders->isEmpty())
            <div class="empty-state">
                <i class="bi bi-receipt"></i>
                <h2 class="section-title fs-3">No orders here yet.</h2>
                <p class="mb-3">{{ request()->filled('search') || request()->filled('status') ? 'Try clearing those filters.' : 'Your first golden moment is waiting to be ordered.' }}</p>
                <a class="btn btn-gold" href="{{ route('menu.index') }}">Browse the menu</a>
            </div>
        @else
            <div class="d-grid gap-3">
                @foreach($orders as $order)
                    <a class="order-card text-decoration-none" href="{{ route('orders.show', $order) }}">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <div class="order-number">{{ $order->order_number }}</div>
                                <div class="small text-muted-bp mt-1">
                                    {{ $order->created_at->format('M d, Y · h:i A') }} · {{ $order->serviceLabel() }}
                                    @if($order->isDineIn())
                                        @if($order->people_count)
                                            · {{ $order->people_count }} {{ \Illuminate\Support\Str::plural('guest', $order->people_count) }}
                                        @endif
                                    @elseif($order->pickup_at)
                                        · Pickup {{ $order->pickup_at->format('M d, h:i A') }}
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                <span class="status-badge {{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
                                <span class="status-badge {{ $order->paymentStatusBadgeClass() }}">{{ $order->paymentStatusLabel() }}</span>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mt-3">
                            <div class="small text-muted-bp">
                                {{ $order->items->sum('quantity') }} {{ \Illuminate\Support\Str::plural('item', $order->items->sum('quantity')) }}
                                · {{ \Illuminate\Support\Str::limit($order->items->pluck('product_name')->join(', '), 80) }}
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-gold font-display fs-5">₱{{ number_format((float) $order->total, 2) }}</span>
                                <span class="text-muted-bp small">View details <i class="bi bi-arrow-right ms-1"></i></span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</section>
@endsection

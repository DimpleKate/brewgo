@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Orders</h1>
        <p class="admin-page-subtitle text-muted-bp">Track every order from pending to completed.</p>
    </div>
</div>

<div class="admin-card mb-3">
    <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2 align-items-end" data-live-filter-form>
        <div class="col-md-5">
            <label class="form-label" for="search">Search</label>
            <div class="search-bp">
                <i class="bi bi-search"></i>
                <input id="search" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Order number, customer name, or email">
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="status">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach(\App\Enums\OrderStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="paymentStatus">Payment</label>
            <select id="paymentStatus" name="payment_status" class="form-select">
                <option value="">All payments</option>
                @foreach([
                    \App\Models\Order::PAYMENT_UNPAID => 'Unpaid',
                    \App\Models\Order::PAYMENT_PENDING => 'Awaiting verification',
                    \App\Models\Order::PAYMENT_DEPOSIT => 'Deposit verified',
                    \App\Models\Order::PAYMENT_PAID => 'Paid in full',
                    \App\Models\Order::PAYMENT_REFUNDED => 'Refunded',
                ] as $value => $label)
                    <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @if(request()->filled('search') || request()->filled('status') || request()->filled('payment_status'))
            <div class="col-auto">
                <a class="btn btn-soft btn-sm" href="{{ route('admin.orders.index') }}">Reset</a>
            </div>
        @endif
    </form>
</div>

<div class="admin-card">
    @if($orders->isEmpty())
        <div class="empty-state">
            <i class="bi bi-receipt"></i>
            <p class="mb-0">No orders match this view.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td>
                                <a class="order-number small" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a>
                                <div class="text-muted-bp admin-meta">{{ $order->created_at->format('M d, Y · h:i A') }}</div>
                            </td>
                            <td>
                                <div class="text-cream">{{ $order->user?->name }}</div>
                                <div class="text-muted-bp admin-meta">{{ $order->user?->email }}</div>
                            </td>
                            <td class="text-muted-bp">
                                {{ $order->serviceLabel() }}
                                @if($order->isDineIn())
                                    @if($order->people_count)
                                        <div class="admin-meta">{{ $order->people_count }} guests</div>
                                    @endif
                                @elseif($order->pickup_at)
                                    <div class="admin-meta">Pickup {{ $order->pickup_at->format('M d, h:i A') }}</div>
                                @endif
                            </td>
                            <td class="text-muted-bp">{{ $order->items_count ?? $order->items()->count() }}</td>
                            <td class="text-gold">₱{{ number_format((float) $order->total, 2) }}</td>
                            <td><span class="status-badge {{ $order->paymentStatusBadgeClass() }}">{{ $order->paymentStatusLabel() }}</span></td>
                            <td><span class="status-badge {{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span></td>
                            <td class="text-end">
                                <a class="btn btn-soft btn-sm" href="{{ route('admin.orders.show', $order) }}">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $orders->links() }}</div>
    @endif
</div>
@endsection

@extends('layouts.admin')

@section('title', $customer->name)

@section('content')
<div class="admin-page-head">
    <div>
        <div class="admin-page-eyebrow">Customer profile</div>
        <h1 class="admin-page-title">{{ $customer->name }}</h1>
        <p class="admin-page-subtitle text-muted-bp">{{ $customer->email }}</p>
    </div>
    <div class="admin-head-actions">
        <a class="btn btn-soft btn-sm" href="{{ route('admin.customers.index') }}"><i class="bi bi-arrow-left me-1"></i>Customers</a>
        <a class="btn btn-soft btn-sm" href="{{ route('admin.customers.export', $customer) }}"><i class="bi bi-download me-1"></i>Export data</a>
        <form action="{{ route('admin.customers.toggle', $customer) }}" method="POST" data-confirm="{{ $customer->is_active ? 'Deactivate this customer account?' : 'Reactivate this customer account?' }}">
            @csrf
            @method('PATCH')
            <button class="btn {{ $customer->is_active ? 'btn-outline-danger' : 'btn-gold' }} btn-sm" type="submit">
                <i class="bi {{ $customer->is_active ? 'bi-person-slash' : 'bi-person-check' }} me-1"></i>{{ $customer->is_active ? 'Deactivate account' : 'Reactivate account' }}
            </button>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4 d-flex flex-column gap-3">
        <div class="admin-card">
            <div class="footer-title">Account</div>
            <div class="d-flex align-items-center gap-3">
                @include('partials.avatar', ['user' => $customer, 'size' => 'lg'])
                <div>
                    <div class="text-cream fw-semibold">{{ $customer->name }}</div>
                    <div class="small text-muted-bp">{{ $customer->email }}</div>
                </div>
            </div>
            <div class="mt-3">
                <div class="summary-row">
                    <span>Status</span>
                    <span class="{{ $customer->is_active ? 'text-success' : 'text-danger' }}">{{ $customer->is_active ? 'Active' : 'Deactivated' }}</span>
                </div>
                <div class="summary-row">
                    <span>Phone</span>
                    <span class="text-cream">{{ $customer->phone ?: '—' }}</span>
                </div>
                <div class="summary-row">
                    <span>Joined</span>
                    <span class="text-cream">{{ $customer->created_at->format('M d, Y') }}</span>
                </div>
                <div class="summary-row">
                    <span>Orders</span>
                    <span class="text-cream">{{ $customer->orders->count() }}</span>
                </div>
                <div class="summary-row">
                    <span>Total spent</span>
                    <span class="text-gold">₱{{ number_format((float) $customer->orders->sum('total'), 2) }}</span>
                </div>
            </div>
            @if($customer->address)
                <div class="promo-hint mt-3">
                    <div class="text-gold fw-semibold mb-1">Address</div>
                    {{ $customer->address }}
                </div>
            @endif
        </div>
    </div>

    <div class="col-xl-8">
        <div class="admin-card">
            <div class="footer-title">Order history</div>
            @if($customer->orders->isEmpty())
                <div class="empty-state">
                    <i class="bi bi-receipt"></i>
                    <p class="mb-0">This customer has not placed an order yet.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table admin-table align-middle">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-end">Open</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customer->orders as $order)
                                <tr>
                                    <td>
                                        <a class="order-number small" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a>
                                        <div class="text-muted-bp admin-meta">{{ $order->created_at->format('M d, Y · h:i A') }}</div>
                                    </td>
                                    <td class="text-muted-bp">{{ $order->items->sum('quantity') }}</td>
                                    <td class="text-gold">₱{{ number_format((float) $order->total, 2) }}</td>
                                    <td><span class="status-badge {{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span></td>
                                    <td class="text-end"><a class="btn btn-soft btn-sm" href="{{ route('admin.orders.show', $order) }}">Open</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

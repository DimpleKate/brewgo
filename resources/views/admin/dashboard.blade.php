@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
    $totalOrders = (int) $statusCounts->sum();
@endphp

<div class="admin-page-head">
    <div>
        <div class="admin-page-eyebrow">Operational overview</div>
        <h1 class="admin-page-title">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
        <p class="admin-page-subtitle text-muted-bp">Here's what's happening at {{ $site['brand_name'] ?? 'Brew & Go' }} today, {{ now()->format('M d, Y') }}.</p>
    </div>
    <div class="admin-head-actions">
        <a class="btn btn-soft btn-sm" href="{{ route('admin.orders.index', ['status' => 'Pending Payment']) }}">
            Pending payment
            @if($stats['pending_orders'] > 0)
                <span class="admin-action-count">{{ $stats['pending_orders'] }}</span>
            @endif
        </a>
        <a class="btn btn-soft btn-sm" href="{{ route('admin.products.create') }}"><i class="bi bi-plus-lg me-1"></i>New product</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card admin-kpi">
            <div class="admin-kpi-top">
                <span class="admin-stat-label">Today's revenue</span>
                <span class="admin-stat-icon"><i class="bi bi-cash-coin"></i></span>
            </div>
            <div class="admin-stat-value">₱{{ number_format($stats['today_revenue'], 2) }}</div>
            <div class="admin-kpi-meta">
                <span class="admin-trend"><i class="bi bi-calendar3 me-1"></i>₱{{ number_format($stats['month_revenue'], 2) }} this month</span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card admin-kpi">
            <div class="admin-kpi-top">
                <span class="admin-stat-label">Today's orders</span>
                <span class="admin-stat-icon"><i class="bi bi-receipt"></i></span>
            </div>
            <div class="admin-stat-value">{{ $stats['today_orders'] }}</div>
            <div class="admin-kpi-meta">
                <span class="admin-trend"><i class="bi bi-graph-up-arrow me-1"></i>{{ $stats['week_orders'] }} in the last 7 days</span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card admin-kpi">
            <div class="admin-kpi-top">
                <span class="admin-stat-label">Active orders</span>
                <span class="admin-stat-icon"><i class="bi bi-fire"></i></span>
            </div>
            <div class="admin-stat-value">{{ $stats['active_orders'] }}</div>
            <div class="admin-kpi-meta">
                <span class="admin-trend {{ $stats['pending_orders'] > 0 ? 'text-warning' : '' }}"><i class="bi bi-hourglass-split me-1"></i>{{ $stats['pending_orders'] }} awaiting payment verification</span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card admin-kpi">
            <div class="admin-kpi-top">
                <span class="admin-stat-label">Customers</span>
                <span class="admin-stat-icon"><i class="bi bi-people"></i></span>
            </div>
            <div class="admin-stat-value">{{ $stats['customers'] }}</div>
            <div class="admin-kpi-meta">
                <span class="admin-trend"><i class="bi bi-inbox me-1"></i>{{ $stats['pending_requests'] }} pending {{ \Illuminate\Support\Str::plural('request', $stats['pending_requests']) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="admin-pulse-strip mb-3">
    <div class="admin-pulse-item">
        <span class="admin-pulse-label">7-day revenue</span>
        <span class="admin-pulse-value">₱{{ number_format($stats['week_revenue'], 2) }}</span>
    </div>
    <div class="admin-pulse-item">
        <span class="admin-pulse-label">Average order</span>
        <span class="admin-pulse-value">₱{{ number_format($stats['average_order_value'], 2) }}</span>
    </div>
    <div class="admin-pulse-item">
        <span class="admin-pulse-label">Live products</span>
        <span class="admin-pulse-value">{{ $stats['active_products'] }} <small>/ {{ $stats['products'] }}</small></span>
    </div>
    <div class="admin-pulse-item">
        <span class="admin-pulse-label">Overdue pickups</span>
        <a class="admin-pulse-value {{ $stats['overdue_orders'] > 0 ? 'text-danger' : '' }}" href="{{ route('admin.orders.index', ['status' => 'Overdue']) }}">{{ $stats['overdue_orders'] }}</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="admin-card h-100">
            <div class="admin-card-head">
                <div>
                    <span class="admin-card-title">Sales overview</span>
                    <span class="admin-card-note">Paid, confirmed, and fulfilled orders · last 7 days</span>
                </div>
                <a class="admin-link" href="{{ route('admin.orders.index') }}">All orders <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="admin-chart" role="img" aria-label="Revenue for the last seven days">
                @foreach($dailySales as $day)
                    <div class="admin-chart-col" title="{{ $day['date'] }}: ₱{{ number_format($day['revenue'], 2) }} across {{ $day['orders'] }} {{ \Illuminate\Support\Str::plural('order', $day['orders']) }}">
                        <span class="admin-chart-amount">₱{{ number_format($day['revenue'], 0) }}</span>
                        <div class="admin-chart-track">
                            <div class="admin-chart-bar" style="height: {{ max(3, (int) round(($day['revenue'] / $maxDailyRevenue) * 100)) }}%"></div>
                        </div>
                        <span class="admin-chart-label">{{ $day['label'] }}</span>
                        <span class="admin-chart-orders">{{ $day['orders'] }} {{ \Illuminate\Support\Str::plural('order', $day['orders']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="admin-card h-100">
            <div class="admin-card-head">
                <div>
                    <span class="admin-card-title">Order pipeline</span>
                    <span class="admin-card-note">{{ $totalOrders }} total orders</span>
                </div>
                <a class="admin-link" href="{{ route('admin.orders.index') }}">Manage</a>
            </div>

            @foreach(\App\Enums\OrderStatus::cases() as $status)
                @php
                    $count = (int) ($statusCounts[$status->value] ?? 0);
                    $percent = $totalOrders > 0 ? (int) round(($count / $totalOrders) * 100) : 0;
                @endphp
                <div class="admin-progress-row">
                    <div class="admin-progress-head">
                        <span class="status-badge {{ $status->badgeClass() }}">{{ $status->label() }}</span>
                        <span class="admin-progress-count">{{ $count }}</span>
                    </div>
                    <div class="admin-progress">
                        <span class="{{ $status->badgeClass() }}" style="width: {{ max($count > 0 ? 3 : 0, $percent) }}%"></span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="admin-card h-100">
            <div class="admin-card-head">
                <div>
                    <span class="admin-card-title">Recent orders</span>
                    <span class="admin-card-note">Latest activity across dine-in and pickup</span>
                </div>
                <a class="admin-link" href="{{ route('admin.orders.index') }}">View all <i class="bi bi-arrow-right"></i></a>
            </div>

            @if($recentOrders->isEmpty())
                <div class="empty-state">
                    <i class="bi bi-receipt"></i>
                    <p class="mb-0">No orders yet. New orders will appear here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table admin-table align-middle">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentOrders as $order)
                                <tr>
                                    <td>
                                        <a class="order-number small" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a>
                                        <div class="text-muted-bp admin-meta">{{ $order->created_at->format('M d · h:i A') }}</div>
                                    </td>
                                    <td>
                                        <div class="text-cream">{{ $order->user?->name }}</div>
                                        <div class="text-muted-bp admin-meta">{{ $order->user?->email }}</div>
                                    </td>
                                    <td class="text-muted-bp">{{ $order->serviceLabel() }}</td>
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

    <div class="col-xl-4 d-flex flex-column gap-3">
        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <span class="admin-card-title">Top products</span>
                    <span class="admin-card-note">By units sold</span>
                </div>
            </div>

            @forelse($topProducts as $index => $product)
                <div class="admin-list-row">
                    <span class="admin-rank">{{ $index + 1 }}</span>
                    <div class="flex-grow-1 min-w-0">
                        <div class="text-cream text-truncate">{{ $product->product_name }}</div>
                        <div class="admin-list-meta">{{ $product->units }} sold</div>
                    </div>
                    <span class="text-gold small">₱{{ number_format((float) $product->revenue, 2) }}</span>
                </div>
            @empty
                <p class="small text-muted-bp mb-0">Product performance appears once orders start coming in.</p>
            @endforelse
        </div>

        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <span class="admin-card-title">Recent customers</span>
                    <span class="admin-card-note">Newest accounts</span>
                </div>
                <a class="admin-link" href="{{ route('admin.customers.index') }}">All</a>
            </div>

            @forelse($recentCustomers as $customer)
                <div class="admin-list-row">
                    <span class="avatar-circle">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                    <div class="flex-grow-1 min-w-0">
                        <a class="text-cream text-decoration-none text-truncate d-block" href="{{ route('admin.customers.show', $customer) }}">{{ $customer->name }}</a>
                        <div class="admin-list-meta">{{ $customer->orders_count }} {{ \Illuminate\Support\Str::plural('order', $customer->orders_count) }} · {{ $customer->created_at->format('M d') }}</div>
                    </div>
                    @if($customer->is_active)
                        <span class="badge text-bg-success">Active</span>
                    @else
                        <span class="badge text-bg-danger">Paused</span>
                    @endif
                </div>
            @empty
                <p class="small text-muted-bp mb-0">No customer accounts yet.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <span class="admin-card-title">Pending requests</span>
                    <span class="admin-card-note">Cancellations and refunds awaiting review</span>
                </div>
                <a class="admin-link" href="{{ route('admin.requests.index', ['status' => 'pending']) }}">Review all</a>
            </div>

            @forelse($pendingRequests as $orderRequest)
                <div class="admin-request-row">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <span class="small text-cream">{{ $orderRequest->typeLabel() }}</span>
                        <span class="badge text-bg-warning">{{ ucfirst($orderRequest->status) }}</span>
                    </div>
                    <a class="small text-gold" href="{{ route('admin.orders.show', $orderRequest->order) }}">{{ $orderRequest->order?->order_number }}</a>
                    <div class="small text-muted-bp mt-1">{{ \Illuminate\Support\Str::limit($orderRequest->reason, 90) }}</div>
                    <div class="timeline-meta mt-1">{{ $orderRequest->user?->name }} · {{ $orderRequest->created_at->diffForHumans() }}</div>
                </div>
            @empty
                <div class="admin-inline-note">
                    <i class="bi bi-check2-circle"></i> No pending cancellation or refund requests.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

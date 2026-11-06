@extends('layouts.admin')

@section('title', 'Refunds')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Refunds</h1>
        <p class="admin-page-subtitle text-muted-bp">Auditable record of every refund issued against an order.</p>
    </div>
</div>

<div class="admin-card mb-3">
    <form method="GET" action="{{ route('admin.refunds.index') }}" class="row g-2 align-items-end" data-live-filter-form>
        <div class="col-md-5">
            <label class="form-label" for="search">Search</label>
            <div class="search-bp">
                <i class="bi bi-search"></i>
                <input id="search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Order number, customer, or reference">
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="status">Status</label>
            <select id="status" name="status" class="form-select form-select-sm">
                <option value="">All statuses</option>
                @foreach(['approved', 'processed', 'rejected'] as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        </div>
        @if($search !== '' || $status !== '')
            <div class="col-auto"><a class="btn btn-link btn-sm" href="{{ route('admin.refunds.index') }}">Reset</a></div>
        @endif
    </form>
</div>

<div class="admin-card">
    @if($refunds->isEmpty())
        <div class="empty-state"><i class="bi bi-arrow-counterclockwise"></i><p class="mb-0">No refunds recorded.</p></div>
    @else
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Processed by</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($refunds as $refund)
                        <tr>
                            <td class="text-muted-bp">{{ ($refund->processed_at ?? $refund->created_at)?->format('M d, Y · h:i A') }}</td>
                            <td>
                                <a class="order-number small" href="{{ route('admin.orders.show', $refund->order) }}">{{ $refund->order?->order_number }}</a>
                                @if($refund->reference)
                                    <div class="text-muted-bp admin-meta">Ref: {{ $refund->reference }}</div>
                                @endif
                            </td>
                            <td class="text-cream">{{ $refund->order?->user?->name ?? $refund->order?->pickup_name ?? '—' }}</td>
                            <td class="text-gold">₱{{ number_format((float) $refund->amount, 2) }}</td>
                            <td class="text-muted-bp">{{ $refund->methodLabel() }}</td>
                            <td><span class="badge text-bg-secondary">{{ ucfirst($refund->status) }}</span></td>
                            <td class="text-muted-bp">{{ $refund->processor?->name ?? 'System' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $refunds->links() }}</div>
    @endif
</div>
@endsection

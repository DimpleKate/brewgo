@extends('layouts.admin')

@section('title', 'Sales reports')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Sales reports</h1>
        <p class="admin-page-subtitle text-muted-bp">Review sales for a date range and export the data.</p>
    </div>
    <div class="admin-head-actions">
        <a class="btn btn-soft btn-sm" href="{{ route('admin.reports.export', ['format' => 'csv', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
        <a class="btn btn-soft btn-sm" href="{{ route('admin.reports.export', ['format' => 'excel', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
        <a class="btn btn-gold btn-sm" href="{{ route('admin.reports.export', ['format' => 'pdf', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"><i class="bi bi-filetype-pdf me-1"></i>PDF</a>
    </div>
</div>

<div class="admin-card mb-3">
    <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label" for="from">From</label>
            <input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="to">To</label>
            <input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <button class="btn btn-soft btn-sm" type="submit">Update report</button>
        </div>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl">
        <div class="admin-card h-100">
            <div class="small text-muted-bp">Orders</div>
            <div class="fs-4 text-cream">{{ number_format($summary['orders']) }}</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="admin-card h-100">
            <div class="small text-muted-bp">Gross sales</div>
            <div class="fs-4 text-cream">₱{{ number_format($summary['gross'], 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="admin-card h-100">
            <div class="small text-muted-bp">Collected</div>
            <div class="fs-4 text-gold">₱{{ number_format($summary['collected'], 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="admin-card h-100">
            <div class="small text-muted-bp">Discounts</div>
            <div class="fs-4 text-cream">₱{{ number_format($summary['discounts'], 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="admin-card h-100">
            <div class="small text-muted-bp">Average order</div>
            <div class="fs-4 text-cream">₱{{ number_format($summary['average'], 2) }}</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="admin-card mb-3">
            <div class="footer-title">Daily collections</div>
            @if($daily->isEmpty())
                <div class="empty-state"><i class="bi bi-graph-up"></i><p class="mb-0">No collections in this range.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table admin-table align-middle mb-0">
                        <thead><tr><th>Date</th><th>Orders</th><th class="text-end">Collected</th></tr></thead>
                        <tbody>
                            @foreach($daily as $day)
                                <tr>
                                    <td class="text-cream">{{ \Carbon\Carbon::parse($day->day)->format('M d, Y') }}</td>
                                    <td class="text-muted-bp">{{ $day->orders }}</td>
                                    <td class="text-end text-gold">₱{{ number_format((float) $day->revenue, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="admin-card">
            <div class="footer-title">Top products</div>
            @if($topProducts->isEmpty())
                <div class="empty-state"><i class="bi bi-cup-hot"></i><p class="mb-0">No product sales in this range.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table admin-table align-middle mb-0">
                        <thead><tr><th>Product</th><th>Quantity</th><th class="text-end">Revenue</th></tr></thead>
                        <tbody>
                            @foreach($topProducts as $product)
                                <tr>
                                    <td class="text-cream">{{ $product->product_name }}</td>
                                    <td class="text-muted-bp">{{ $product->quantity }}</td>
                                    <td class="text-end text-gold">₱{{ number_format((float) $product->revenue, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="col-xl-5">
        <div class="admin-card mb-3">
            <div class="footer-title">By payment method</div>
            @forelse($paymentMethods as $method)
                <div class="summary-row">
                    <span>{{ $method->payment_method === \App\Models\Payment::METHOD_CASH ? 'Cash' : 'GCash / QR' }} <span class="text-muted-bp">({{ $method->orders }})</span></span>
                    <span class="text-gold">₱{{ number_format((float) $method->revenue, 2) }}</span>
                </div>
            @empty
                <p class="small text-muted-bp mb-0">No payments in this range.</p>
            @endforelse
        </div>

        <div class="admin-card">
            <div class="footer-title">By service</div>
            @forelse($services as $service)
                <div class="summary-row">
                    <span>{{ $service->service_type === \App\Models\Order::SERVICE_DINE_IN ? 'Dine-in' : 'Pickup' }} <span class="text-muted-bp">({{ $service->orders }})</span></span>
                    <span class="text-gold">₱{{ number_format((float) $service->revenue, 2) }}</span>
                </div>
            @empty
                <p class="small text-muted-bp mb-0">No payments in this range.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

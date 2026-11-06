@extends('layouts.app')

@section('title', 'Receipt '.$receipt->receipt_number.' | '.$site['brand_name'])

@php
    $snapshot = $receipt->snapshot ?? [];
    $items = $snapshot['items'] ?? [];
    $order = $receipt->order;
@endphp

@section('content')
<section class="page-hero">
    <div class="container">
        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3">
            <div>
                <span class="eyebrow">Digital receipt</span>
                <h1 class="mb-2"><span class="gold-text">{{ $receipt->receipt_number }}</span></h1>
                <p class="section-subtitle mb-0">Issued {{ $receipt->issued_at->format('M d, Y · h:i A') }} · {{ $receipt->methodLabel() }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="status-badge {{ $receipt->balance_due > 0 ? 'text-bg-warning' : 'text-bg-success' }}">
                    {{ $receipt->balance_due > 0 ? 'Deposit verified' : 'Paid in full' }}
                </span>
                <a class="btn btn-soft btn-sm" href="{{ route('orders.show', $order) }}"><i class="bi bi-arrow-left me-1"></i>Order</a>
            </div>
        </div>
    </div>
</section>

<section class="section-pad pt-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="panel panel-pad">
                    <div class="d-flex flex-column flex-sm-row justify-content-between gap-3 pb-3 border-bottom border-secondary-subtle">
                        <div>
                            <div class="footer-title mb-1">{{ $site['brand_name'] }}</div>
                            <div class="small text-muted-bp">{{ $site['brand_tagline'] ?? '' }}</div>
                            @if(! empty($site['contact_phone']))
                                <div class="small text-muted-bp">{{ $site['contact_phone'] }}</div>
                            @endif
                        </div>
                        <div class="text-sm-end">
                            <div class="small text-muted-bp">Receipt number</div>
                            <div class="text-gold fw-semibold">{{ $receipt->receipt_number }}</div>
                            <div class="small text-muted-bp mt-1">{{ $receipt->issued_at->format('M d, Y · h:i A') }}</div>
                        </div>
                    </div>

                    <div class="row g-3 py-3 border-bottom border-secondary-subtle">
                        <div class="col-sm-6">
                            <div class="small text-muted-bp">Order number</div>
                            <div class="text-cream fw-semibold">{{ $snapshot['order_number'] ?? $order->order_number }}</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="small text-muted-bp">Service</div>
                            <div class="text-cream">{{ $order->serviceLabel() }}</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="small text-muted-bp">{{ $order->isDineIn() ? 'Guests' : 'Pickup' }}</div>
                            <div class="text-cream">
                                @if($order->isDineIn())
                                    {{ $order->people_count ?: '—' }}
                                @else
                                    {{ $snapshot['pickup_at'] ? \Carbon\Carbon::parse($snapshot['pickup_at'])->format('M d, h:i A') : ($order->pickup_at?->format('M d, h:i A') ?: '—') }}
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="py-3">
                        @foreach($items as $item)
                            <div class="py-2 border-bottom border-secondary-subtle">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div>
                                        <div class="text-cream fw-semibold">{{ $item['product_name'] }}</div>
                                        @if(! empty($item['options']))
                                            <div class="small text-muted-bp">
                                                {{ collect($item['options'])->pluck('name')->filter()->join(', ') }}
                                            </div>
                                        @endif
                                        @if(! empty($item['addons']))
                                            <div class="small text-muted-bp">
                                                Add-ons: {{ collect($item['addons'])->pluck('name')->filter()->join(', ') }}
                                            </div>
                                        @endif
                                        @if(! empty($item['note']))
                                            <div class="small text-gold">Note: {{ $item['note'] }}</div>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        <div class="text-cream">₱{{ number_format((float) ($item['line_total'] ?? 0), 2) }}</div>
                                        <div class="small text-muted-bp">{{ $item['quantity'] }} × ₱{{ number_format((float) ($item['unit_price'] ?? 0), 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span class="text-cream">₱{{ number_format((float) ($snapshot['subtotal'] ?? $order->subtotal), 2) }}</span>
                    </div>
                    @if((float) ($snapshot['discount'] ?? $order->discount) > 0)
                        <div class="summary-row">
                            <span>Discount</span>
                            <span class="text-success">-₱{{ number_format((float) ($snapshot['discount'] ?? $order->discount), 2) }}</span>
                        </div>
                    @endif
                    <div class="summary-total">
                        <span>Total</span>
                        <span class="text-gold">₱{{ number_format((float) ($snapshot['total'] ?? $order->total), 2) }}</span>
                    </div>

                    <div class="summary-row mt-2">
                        <span>Paid on this receipt</span>
                        <span class="text-cream">₱{{ number_format((float) $receipt->amount, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Balance due</span>
                        <span class="{{ (float) $receipt->balance_due > 0 ? 'text-gold' : 'text-success' }}">₱{{ number_format((float) $receipt->balance_due, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Payment method</span>
                        <span class="text-cream">{{ $receipt->methodLabel() }}</span>
                    </div>
                    @if(! empty($snapshot['reference']))
                        <div class="summary-row">
                            <span>Reference</span>
                            <span class="text-cream">{{ $snapshot['reference'] }}</span>
                        </div>
                    @endif

                    <div class="small text-muted-bp mt-3">
                        This is a digital receipt for a verified payment. A payment is never marked as paid before staff verification. Keep this receipt for cancellations, refunds, or pickup questions.
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button class="btn btn-gold" type="button" data-print-receipt><i class="bi bi-printer me-1"></i>Print receipt</button>
                        <a class="btn btn-soft" href="{{ route('receipts.pdf', $receipt) }}"><i class="bi bi-filetype-pdf me-1"></i>Download PDF</a>
                        <a class="btn btn-soft" href="{{ route('orders.show', $order) }}">Back to order</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.querySelector('[data-print-receipt]')?.addEventListener('click', () => window.print());
</script>
@endpush

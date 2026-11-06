@extends('layouts.admin')

@section('title', 'Order '.$order->order_number)

@section('content')
@php
    $paymentReadyStatuses = [\App\Enums\OrderStatus::Paid, \App\Enums\OrderStatus::Preparing, \App\Enums\OrderStatus::Ready];
    $allowedStatuses = collect(\App\Enums\OrderStatus::cases())
        ->filter(fn ($status) => $order->status->canTransitionTo($status))
        ->filter(fn ($status) => ! in_array($status, $paymentReadyStatuses, true) || $order->isClearedForPreparation());
    $pendingRequests = $order->requests->where('status', \App\Models\OrderRequest::STATUS_PENDING);
    $pendingPayments = $order->payments->where('status', \App\Models\Payment::STATUS_PENDING);
    $balanceDue = $order->balanceDue();
@endphp

<div class="admin-page-head">
    <div>
        <div class="admin-page-eyebrow">Admin · order detail</div>
        <h1 class="admin-page-title">{{ $order->order_number }}</h1>
        <p class="admin-page-subtitle text-muted-bp">{{ $order->created_at->format('M d, Y · h:i A') }} · {{ $order->serviceLabel() }}</p>
    </div>
    <div class="admin-head-actions">
        <span class="status-badge {{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
        <a class="btn btn-soft btn-sm" href="{{ route('admin.orders.index') }}"><i class="bi bi-arrow-left me-1"></i>Orders</a>
    </div>
</div>

@if($order->status === \App\Enums\OrderStatus::Overdue)
    <div class="promo-hint mb-3">
        <div class="text-danger fw-semibold mb-1"><i class="bi bi-alarm me-1"></i>Overdue pickup</div>
        This order passed its pickup deadline on {{ $order->pickup_deadline_at?->format('M d, Y · h:i A') }}. Contact the customer, or cancel the order to record the prepared items as waste.
    </div>
@endif

<div class="row g-3">
    <div class="col-xl-8 d-flex flex-column gap-3">
        <div class="admin-card">
            <div class="footer-title">Items</div>
            @foreach($order->items as $item)
                <div class="py-2 border-bottom border-secondary-subtle">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                        <div>
                            <div class="text-cream fw-semibold">{{ $item->product_name }}</div>
                            <div class="product-category">{{ $item->category_name }}</div>
                        </div>
                        <div class="text-gold">₱{{ number_format((float) $item->line_total, 2) }}</div>
                    </div>

                    @if(! empty($item->options_snapshot) || ! empty($item->addons_snapshot))
                        <div class="mt-2">
                            @foreach($item->options_snapshot ?? [] as $option)
                                @php
                                    $absolutePrice = ($option['group'] ?? null) === 'size' && isset($option['price']) ? (float) $option['price'] : null;
                                @endphp
                                <span class="snapshot-option">{{ $option['name'] ?? 'Option' }}@if($absolutePrice !== null) (₱{{ number_format($absolutePrice, 2) }})@elseif((float) ($option['price_adjustment'] ?? 0) !== 0.0) ({{ (float) $option['price_adjustment'] > 0 ? '+' : '-' }}₱{{ number_format(abs((float) $option['price_adjustment']), 2) }})@endif</span>
                            @endforeach
                            @foreach($item->addons_snapshot ?? [] as $addon)
                                <span class="snapshot-option">+ {{ $addon['name'] ?? 'Add-on' }} (₱{{ number_format((float) ($addon['price'] ?? 0), 2) }})</span>
                            @endforeach
                        </div>
                    @endif

                    @if($item->note)
                        <div class="promo-hint mt-2">
                            <span class="text-gold fw-semibold">Special instructions:</span> {{ $item->note }}
                        </div>
                    @endif

                    <div class="small text-muted-bp mt-2">{{ $item->quantity }} × ₱{{ number_format((float) $item->unit_price, 2) }}</div>
                </div>
            @endforeach

            <div class="mt-3">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="text-cream">₱{{ number_format((float) $order->subtotal, 2) }}</span>
                </div>
                @if((float) $order->discount > 0)
                    <div class="summary-row">
                        <span>Discount{{ $order->promotion_code ? ' ('.$order->promotion_code.')' : '' }}</span>
                        <span class="text-success">-₱{{ number_format((float) $order->discount, 2) }}</span>
                    </div>
                @endif
                <div class="summary-total">
                    <span>Total</span>
                    <span class="text-gold">₱{{ number_format((float) $order->total, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="footer-title">Status history</div>
            <div class="timeline">
                @foreach($order->statusHistories as $history)
                    <div class="timeline-item">
                        <p class="timeline-title">{{ $history->status }}</p>
                        <div class="timeline-meta">
                            {{ $history->created_at->format('M d, Y · h:i A') }}
                            @if($history->changedBy) · {{ $history->changedBy->name }} @endif
                        </div>
                        @if($history->note)
                            <div class="small text-muted-bp mt-1">{{ $history->note }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        @if($order->notes)
            <div class="admin-card">
                <div class="footer-title">Customer notes</div>
                <p class="small mb-0">{{ $order->notes }}</p>
            </div>
        @endif

        @if($order->wasteItems->isNotEmpty())
            <div class="admin-card">
                <div class="footer-title">Wasted items</div>
                <p class="small text-muted-bp">Prepared items recorded when this order was cancelled or left unclaimed.</p>
                @foreach($order->wasteItems as $waste)
                    <div class="d-flex align-items-start justify-content-between gap-2 py-2 border-bottom border-secondary-subtle">
                        <div>
                            <div class="small text-cream">{{ $waste->quantity }}× {{ $waste->product_name }}</div>
                            <div class="small text-muted-bp">{{ $waste->reason }}</div>
                            <div class="small text-muted-bp">{{ $waste->created_at->format('M d, Y · h:i A') }}@if($waste->logger) · {{ $waste->logger->name }}@endif</div>
                        </div>
                    </div>
                @endforeach
                <div class="small text-gold mt-2">Total wasted: {{ $order->wasteItems->sum('quantity') }} item(s)</div>
            </div>
        @endif
    </div>

    <div class="col-xl-4 d-flex flex-column gap-3">
        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <span class="admin-card-title">Payment</span>
                    <span class="admin-card-note">Only verified payments are marked paid.</span>
                </div>
                <span class="status-badge {{ $order->paymentStatusBadgeClass() }}">{{ $order->paymentStatusLabel() }}</span>
            </div>

            <div class="d-flex justify-content-between gap-2 small py-1">
                <span class="text-muted-bp">Required before preparation</span>
                <span class="text-cream">₱{{ number_format((float) $order->required_payment_amount, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between gap-2 small py-1">
                <span class="text-muted-bp">Verified paid</span>
                <span class="text-cream">₱{{ number_format((float) $order->amount_paid, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between gap-2 small py-1">
                <span class="text-muted-bp">Balance due</span>
                <span class="text-gold">₱{{ number_format($balanceDue, 2) }}</span>
            </div>

            @if($pendingPayments->isNotEmpty())
                <div class="border-top border-secondary-subtle mt-3 pt-3">
                    <div class="small text-gold fw-semibold mb-2">Awaiting verification</div>
                    @foreach($pendingPayments as $payment)
                        <div class="border-bottom border-secondary-subtle pb-2 mb-2">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="small text-cream">{{ $payment->methodLabel() }} · {{ $payment->typeLabel() }}</span>
                                <span class="text-gold">₱{{ number_format((float) $payment->amount, 2) }}</span>
                            </div>
                            @if($payment->reference)
                                <div class="small text-muted-bp mt-1">Reference: {{ $payment->reference }}</div>
                            @endif
                            @if($payment->proof_path)
                                <a class="payment-proof" href="{{ $payment->proofUrl() }}" target="_blank" rel="noopener">
                                    <img src="{{ $payment->proofUrl() }}" alt="Payment proof for {{ $order->order_number }}" class="payment-proof-thumb">
                                    <span><i class="bi bi-box-arrow-up-right me-1"></i>View proof of payment</span>
                                </a>
                            @endif
                            <div class="small text-muted-bp">Submitted {{ $payment->created_at->format('M d, Y · h:i A') }}</div>

                            <form action="{{ route('admin.orders.payments.verify', [$order, $payment]) }}" method="POST" class="mt-2" data-submit-once>
                                @csrf
                                @method('PATCH')
                                <input type="text" name="note" class="form-control form-control-sm mb-2" maxlength="255" placeholder="Verification note (optional)">
                                <button class="btn btn-gold btn-sm w-100" type="submit"><i class="bi bi-check2-circle me-1"></i>Verify payment</button>
                            </form>

                            <form action="{{ route('admin.orders.payments.reject', [$order, $payment]) }}" method="POST" class="mt-2" data-submit-once data-confirm="Reject this payment? The order will not be marked paid.">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="rejection_reason" class="form-control form-control-sm mb-2" maxlength="255" placeholder="Reason for rejection" required>
                                <button class="btn btn-soft btn-sm w-100" type="submit">Reject payment</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($balanceDue > 0)
                <div class="border-top border-secondary-subtle mt-3 pt-3">
                    <div class="small text-gold fw-semibold mb-2">Record a verified payment</div>
                    <form action="{{ route('admin.orders.payments.record', $order) }}" method="POST" data-submit-once>
                        @csrf
                        <label class="form-label" for="paymentMethod">Method</label>
                        <select id="paymentMethod" name="method" class="form-select form-select-sm mb-2" required>
                            <option value="cash">Cash at cashier</option>
                            <option value="qr">GCash / QR</option>
                        </select>
                        <label class="form-label" for="paymentAmount">Amount</label>
                        <input id="paymentAmount" type="number" step="0.01" min="0.01" max="{{ number_format($balanceDue, 2, '.', '') }}" name="amount" value="{{ number_format($balanceDue, 2, '.', '') }}" class="form-control form-control-sm mb-2" required>
                        <label class="form-label" for="paymentReference">GCash / QR reference <span class="text-muted-bp">(for QR)</span></label>
                        <input id="paymentReference" type="text" name="reference" class="form-control form-control-sm mb-2" maxlength="80">
                        <label class="form-label" for="paymentNotes">Note</label>
                        <input id="paymentNotes" type="text" name="notes" class="form-control form-control-sm mb-2" maxlength="255">
                        <button class="btn btn-soft btn-sm w-100" type="submit">Record verified payment</button>
                    </form>
                </div>
            @endif

            @if($order->receipts->isNotEmpty())
                <div class="border-top border-secondary-subtle mt-3 pt-3">
                    <div class="small text-gold fw-semibold mb-2">Digital receipts</div>
                    @foreach($order->receipts as $receipt)
                        <a class="btn btn-soft btn-sm w-100 mb-2" href="{{ route('receipts.show', $receipt) }}">
                            <i class="bi bi-receipt me-1"></i>{{ $receipt->receipt_number }} · ₱{{ number_format((float) $receipt->amount, 2) }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="admin-card">
            <div class="footer-title">Update status</div>
            <form action="{{ route('admin.orders.status', $order) }}" method="POST" data-submit-once>
                @csrf
                @method('PATCH')
                <label class="form-label" for="status">New status</label>
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                    @foreach($allowedStatuses as $status)
                        <option value="{{ $status->value }}" @selected($order->status === $status)>{{ $status->label() }}{{ $order->status === $status ? ' (current)' : '' }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <label class="form-label mt-3" for="note">Internal note <span class="text-muted-bp">(optional)</span></label>
                <textarea id="note" name="note" class="form-control @error('note') is-invalid @enderror" rows="2" maxlength="255" placeholder="Optional note for the status history.">{{ old('note') }}</textarea>
                @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <button class="btn btn-gold btn-sm w-100 mt-3" type="submit">Update order</button>
            </form>
        </div>

        <div class="admin-card">
            <div class="footer-title">Customer</div>
            <div class="text-cream">{{ $order->user?->name }}</div>
            <div class="small text-muted-bp">{{ $order->user?->email }}</div>
            @if($order->user?->phone)
                <div class="small text-muted-bp">{{ $order->user->phone }}</div>
            @endif
            @if($order->user)
                <a class="btn btn-soft btn-sm w-100 mt-2" href="{{ route('admin.customers.show', $order->user) }}">View customer</a>
            @endif
        </div>

        <div class="admin-card">
            <div class="footer-title">Service details</div>
            <div class="d-flex justify-content-between gap-2 small py-1">
                <span class="text-muted-bp">Type</span>
                <span class="text-cream">{{ $order->serviceLabel() }}</span>
            </div>
            @if($order->isDineIn())
                <div class="d-flex justify-content-between gap-2 small py-1">
                    <span class="text-muted-bp">Guests</span>
                    <span class="text-cream">{{ $order->people_count }}</span>
                </div>
            @else
                <div class="d-flex justify-content-between gap-2 small py-1">
                    <span class="text-muted-bp">Pickup name</span>
                    <span class="text-cream">{{ $order->pickup_name }}</span>
                </div>
                <div class="d-flex justify-content-between gap-2 small py-1">
                    <span class="text-muted-bp">Contact</span>
                    <span class="text-cream">{{ $order->pickup_phone }}</span>
                </div>
                <div class="d-flex justify-content-between gap-2 small py-1">
                    <span class="text-muted-bp">Pickup time</span>
                    <span class="text-cream">{{ $order->pickup_at?->format('M d, Y · h:i A') ?: '—' }}</span>
                </div>
                <div class="d-flex justify-content-between gap-2 small py-1">
                    <span class="text-muted-bp">Collect by</span>
                    <span class="text-cream">{{ $order->pickup_deadline_at?->format('M d, Y · h:i A') ?: '—' }}</span>
                </div>
            @endif
        </div>

        <div class="admin-card">
            <div class="footer-title">Refunds</div>
            @php
                $refundedTotal = $order->refunds->whereIn('status', [\App\Models\Refund::STATUS_APPROVED, \App\Models\Refund::STATUS_PROCESSED])->sum('amount');
                $refundableTotal = max(0, (float) $order->amount_paid - (float) $refundedTotal);
            @endphp

            <div class="summary-row">
                <span>Paid</span>
                <span class="text-cream">₱{{ number_format((float) $order->amount_paid, 2) }}</span>
            </div>
            <div class="summary-row">
                <span>Refunded</span>
                <span class="text-cream">₱{{ number_format((float) $refundedTotal, 2) }}</span>
            </div>
            <div class="summary-row">
                <span>Refundable</span>
                <span class="text-gold">₱{{ number_format($refundableTotal, 2) }}</span>
            </div>

            @if($order->refunds->isNotEmpty())
                <div class="mt-2 mb-3">
                    @foreach($order->refunds as $refund)
                        <div class="border-bottom border-secondary-subtle pb-2 mb-2">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="small text-cream">₱{{ number_format((float) $refund->amount, 2) }} · {{ $refund->methodLabel() }}</span>
                                <span class="badge text-bg-secondary">{{ ucfirst($refund->status) }}</span>
                            </div>
                            <div class="small text-muted-bp">
                                {{ ($refund->processed_at ?? $refund->created_at)?->format('M d, Y · h:i A') }}
                                · {{ $refund->processor?->name ?? 'System' }}
                            </div>
                            @if($refund->reference)
                                <div class="small text-muted-bp">Ref: {{ $refund->reference }}</div>
                            @endif
                            @if($refund->reason)
                                <div class="small text-muted-bp">{{ $refund->reason }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if($refundableTotal > 0)
                <form action="{{ route('admin.orders.refunds.store', $order) }}" method="POST" data-submit-once>
                    @csrf
                    <label class="form-label" for="refundAmount">Amount</label>
                    <input id="refundAmount" type="number" step="0.01" min="0.01" max="{{ number_format($refundableTotal, 2, '.', '') }}" name="amount" value="{{ number_format($refundableTotal, 2, '.', '') }}" class="form-control form-control-sm mb-2" required>

                    <label class="form-label" for="refundMethod">Method</label>
                    <select id="refundMethod" name="method" class="form-select form-select-sm mb-2" required>
                        <option value="cash">Cash</option>
                        <option value="qr">GCash / QR</option>
                    </select>

                    <label class="form-label" for="refundReference">Reference <span class="text-muted-bp">(for QR)</span></label>
                    <input id="refundReference" type="text" name="reference" maxlength="80" class="form-control form-control-sm mb-2">

                    <label class="form-label" for="refundReason">Reason</label>
                    <input id="refundReason" type="text" name="reason" maxlength="255" class="form-control form-control-sm mb-2" required>

                    <label class="form-label" for="refundNotes">Notes <span class="text-muted-bp">(optional)</span></label>
                    <textarea id="refundNotes" name="notes" rows="2" maxlength="500" class="form-control form-control-sm mb-2"></textarea>

                    <button class="btn btn-gold btn-sm w-100" type="submit">Record refund</button>
                </form>
            @else
                <p class="small text-muted-bp mb-0 mt-2">No refundable amount remains on this order.</p>
            @endif
        </div>

        <div class="admin-card">
            <div class="footer-title">Cancellation / refund requests</div>
            @forelse($order->requests as $orderRequest)
                <div class="border-bottom border-secondary-subtle pb-2 mb-2">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <span class="small text-cream">{{ $orderRequest->typeLabel() }}</span>
                        <span class="badge {{ $orderRequest->statusBadgeClass() }}">{{ ucfirst($orderRequest->status) }}</span>
                    </div>
                    <div class="small text-muted-bp mt-1">{{ $orderRequest->reason }}</div>
                    @if($orderRequest->admin_note)
                        <div class="small text-gold mt-1">Admin: {{ $orderRequest->admin_note }}</div>
                    @endif
                    @if($orderRequest->status === \App\Models\OrderRequest::STATUS_PENDING)
                        <form action="{{ route('admin.requests.review', $orderRequest) }}" method="POST" class="mt-2">
                            @csrf
                            @method('PATCH')
                            <label class="form-label" for="decision-{{ $orderRequest->id }}">Decision</label>
                            <select id="decision-{{ $orderRequest->id }}" name="decision" class="form-select form-select-sm" required>
                                <option value="approved">Approve</option>
                                <option value="rejected">Reject</option>
                            </select>
                            <label class="form-label mt-2" for="admin-note-{{ $orderRequest->id }}">Admin note</label>
                            <textarea id="admin-note-{{ $orderRequest->id }}" name="admin_note" class="form-control form-control-sm" rows="2" maxlength="1000" placeholder="Required when rejecting."></textarea>
                            <button class="btn btn-soft btn-sm w-100 mt-2" type="submit">Submit review</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="small text-muted-bp mb-0">No requests for this order.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

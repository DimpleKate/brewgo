@extends('layouts.app')

@section('title', 'Order '.$order->order_number.' | '.$site['brand_name'])

@section('content')
<section class="page-hero">
    <div class="container">
        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3">
            <div>
                <span class="eyebrow">Order details</span>
                <h1 class="mb-2"><span class="gold-text">{{ $order->order_number }}</span></h1>
                <p class="section-subtitle mb-0">Placed {{ $order->created_at->format('M d, Y · h:i A') }} · {{ $order->serviceLabel() }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="status-badge {{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
                <a class="btn btn-soft btn-sm" href="{{ route('orders.index') }}"><i class="bi bi-arrow-left me-1"></i>All orders</a>
            </div>
        </div>
    </div>
</section>

<section class="section-pad pt-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="panel panel-pad mb-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <div class="footer-title mb-0">Order progress</div>
                        @if($order->promotion_code)
                            <span class="small text-gold"><i class="bi bi-ticket-perforated me-1"></i>{{ $order->promotion_code }}</span>
                        @endif
                    </div>

                    @php
                        $flow = ['Pending Payment', 'Paid', 'Preparing', 'Ready', 'Completed'];
                        $currentIndex = array_search($order->status->value, $flow, true);

                        if ($currentIndex === false) {
                            $currentIndex = match ($order->status->value) {
                                'Pending' => 0,
                                'Confirmed' => 1,
                                default => false,
                            };
                        }

                        $isSpecial = in_array($order->status->value, ['Rejected', 'Cancelled', 'Overdue'], true);
                    @endphp

                    <div class="order-track">
                        @foreach($flow as $index => $step)
                            <div class="order-step {{ ! $isSpecial && $currentIndex !== false && $index <= $currentIndex ? 'done' : '' }} {{ ! $isSpecial && $currentIndex === $index ? 'current' : '' }}">
                                <div class="dot">
                                    @if(! $isSpecial && $currentIndex !== false && $index < $currentIndex)
                                        <i class="bi bi-check2"></i>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </div>
                                <div class="label">{{ $step }}</div>
                            </div>
                        @endforeach
                    </div>

                    @if($isSpecial)
                        <div class="promo-hint mt-3">
                            <i class="bi bi-info-circle me-1"></i> This order was {{ strtolower($order->status->value) }}.
                            @if($order->status === \App\Enums\OrderStatus::Overdue)
                                It was not collected by the pickup deadline. Please contact us if you still want to collect it.
                            @endif
                            @if($order->requests->firstWhere('status', \App\Models\OrderRequest::STATUS_APPROVED))
                                An approved request is recorded below.
                            @endif
                        </div>
                    @endif
                </div>

                <div class="panel panel-pad mb-4">
                    <div class="footer-title">Your items</div>

                    @foreach($order->items as $item)
                        <div class="cart-item">
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                    <div>
                                        <div class="cart-item-name">{{ $item->product_name }}</div>
                                        <div class="product-category">{{ $item->category_name }}</div>
                                    </div>
                                    <div class="cart-price">₱{{ number_format((float) $item->line_total, 2) }}</div>
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
                                    <div class="small text-gold mt-2">
                                        <i class="bi bi-chat-left-text me-1"></i>Special instructions: {{ $item->note }}
                                    </div>
                                @endif

                                <div class="small text-muted-bp mt-2">
                                    {{ $item->quantity }} × ₱{{ number_format((float) $item->unit_price, 2) }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="panel panel-pad">
                    <div class="footer-title">Activity</div>
                    <div class="timeline">
                        @foreach($order->statusHistories as $history)
                            <div class="timeline-item">
                                <p class="timeline-title">{{ $history->status }}</p>
                                <div class="timeline-meta">
                                    {{ $history->created_at->format('M d, Y · h:i A') }}
                                    @if($history->changedBy)
                                        · {{ $history->changedBy->isAdmin() ? $site['brand_name'].' team' : $history->changedBy->name }}
                                    @endif
                                </div>
                                @if($history->note)
                                    <div class="small text-muted-bp mt-1">{{ $history->note }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <aside class="panel panel-pad mb-4">
                    <div class="footer-title">Summary</div>
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
                    @if((float) $order->balanceDue() > 0)
                        <div class="small text-muted-bp mt-2">
                            @if($order->isDineIn())
                                Please settle ₱{{ number_format($order->balanceDue(), 2) }} at the cashier. Your order is prepared only after payment is verified.
                            @else
                                ₱{{ number_format($order->balanceDue(), 2) }} remains due when you collect this order.
                            @endif
                        </div>
                    @else
                        <div class="small text-success mt-2"><i class="bi bi-check2-circle me-1"></i>Paid in full.</div>
                    @endif
                </aside>

                <aside class="panel panel-pad mb-4">
                    <div class="footer-title">Payment</div>
                    <div class="d-flex justify-content-between gap-2 small py-1">
                        <span class="text-muted-bp">Status</span>
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
                        <span class="text-muted-bp">Balance</span>
                        <span class="text-gold">₱{{ number_format($order->balanceDue(), 2) }}</span>
                    </div>
                    @if($order->payment_method)
                        <div class="d-flex justify-content-between gap-2 small py-1">
                            <span class="text-muted-bp">Method</span>
                            <span class="text-cream">{{ $order->payment_method === \App\Models\Payment::METHOD_CASH ? 'Cash at cashier' : 'GCash / QR' }}</span>
                        </div>
                    @endif
                    @if($order->paid_at)
                        <div class="d-flex justify-content-between gap-2 small py-1">
                            <span class="text-muted-bp">First verified</span>
                            <span class="text-cream">{{ $order->paid_at->format('M d, Y · h:i A') }}</span>
                        </div>
                    @endif
                    @if($order->payments->where('status', \App\Models\Payment::STATUS_PENDING)->isNotEmpty())
                        <div class="promo-hint mt-2 small">
                            <i class="bi bi-hourglass-split me-1"></i> A payment is awaiting staff verification. This order will not be prepared until it is verified.
                        </div>
                    @endif
                    @if($order->payments->isNotEmpty())
                        <div class="mt-3">
                            <div class="small text-gold fw-semibold mb-2">Payment attempts</div>
                            @foreach($order->payments as $payment)
                                <div class="border-bottom border-secondary-subtle pb-2 mb-2">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <span class="small text-cream">{{ $payment->methodLabel() }} · {{ $payment->typeLabel() }}</span>
                                        <span class="badge {{ $payment->statusBadgeClass() }}">{{ ucfirst($payment->status) }}</span>
                                    </div>
                                    <div class="small text-muted-bp mt-1">
                                        ₱{{ number_format((float) $payment->amount, 2) }}
                                        · {{ $payment->created_at->format('M d, Y · h:i A') }}
                                    </div>
                                    @if($payment->reference)
                                        <div class="small text-muted-bp">Reference: {{ $payment->reference }}</div>
                                    @endif
                                    @if($payment->status === \App\Models\Payment::STATUS_REJECTED && $payment->rejection_reason)
                                        <div class="small text-danger mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $payment->rejection_reason }}</div>
                                    @endif
                                    @if($payment->hasProof())
                                        <a class="small" href="{{ $payment->proofUrl() }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>View uploaded proof</a>
                                    @endif

                                    @if($payment->isPending())
                                        <form action="{{ route('orders.payments.proof.update', [$order, $payment]) }}" method="POST" enctype="multipart/form-data" class="mt-2" data-submit-once>
                                            @csrf
                                            <label class="form-label small" for="proof-{{ $payment->id }}">Replace proof of payment</label>
                                            <input id="proof-{{ $payment->id }}" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.webp" class="form-control form-control-sm" required>
                                            <input type="text" name="payment_reference" maxlength="80" class="form-control form-control-sm mt-2" value="{{ $payment->reference }}" placeholder="GCash reference number">
                                            <button class="btn btn-soft btn-sm w-100 mt-2" type="submit">Submit new proof</button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @php
                        $hasPendingPayment = $order->payments->contains(fn ($payment) => $payment->status === \App\Models\Payment::STATUS_PENDING);
                        $outstanding = max((float) $order->required_payment_amount - (float) $order->amount_paid, 0);
                        $canRetryPayment = ! $hasPendingPayment
                            && ! $order->status->isFinal()
                            && ($outstanding > 0 || $order->balanceDue() > 0);
                    @endphp

                    @if($canRetryPayment)
                        <div class="promo-hint mt-3">
                            <div class="small text-gold fw-semibold mb-2">Submit a new payment</div>
                            <form action="{{ route('orders.payments.retry', $order) }}" method="POST" enctype="multipart/form-data" data-submit-once>
                                @csrf
                                <label class="form-label small" for="retryMethod">Payment method</label>
                                <select id="retryMethod" name="method" class="form-select form-select-sm" required>
                                    <option value="qr">GCash / QR</option>
                                    <option value="cash">Cash at cashier</option>
                                </select>
                                <label class="form-label small mt-2" for="retryReference">GCash reference <span class="text-muted-bp">(for QR)</span></label>
                                <input id="retryReference" type="text" name="payment_reference" maxlength="80" class="form-control form-control-sm">
                                <label class="form-label small mt-2" for="retryProof">Proof of payment <span class="text-muted-bp">(for QR)</span></label>
                                <input id="retryProof" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.webp" class="form-control form-control-sm">
                                @error('method')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                @error('payment_reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                @error('payment_proof')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <button class="btn btn-gold btn-sm w-100 mt-2" type="submit">Submit payment for verification</button>
                            </form>
                        </div>
                    @endif

                    @if($order->receipts->isNotEmpty())
                        <div class="mt-3">
                            <div class="small text-gold fw-semibold mb-2">Digital receipts</div>
                            @foreach($order->receipts as $receipt)
                                <a class="btn btn-soft btn-sm w-100 mb-2" href="{{ route('receipts.show', $receipt) }}">
                                    <i class="bi bi-receipt me-1"></i>{{ $receipt->receipt_number }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </aside>

                <aside class="panel panel-pad mb-4">
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
                        @if($order->pickup_at)
                            <div class="d-flex justify-content-between gap-2 small py-1">
                                <span class="text-muted-bp">Pickup time</span>
                                <span class="text-cream">{{ $order->pickup_at->format('M d, Y · h:i A') }}</span>
                            </div>
                        @endif
                        @if($order->pickup_deadline_at)
                            <div class="d-flex justify-content-between gap-2 small py-1">
                                <span class="text-muted-bp">Collect by</span>
                                <span class="text-cream">{{ $order->pickup_deadline_at->format('M d, Y · h:i A') }}</span>
                            </div>
                        @endif
                    @endif
                    @if($order->notes)
                        <div class="promo-hint mt-3">
                            <div class="text-gold fw-semibold mb-1">Order notes</div>
                            {{ $order->notes }}
                        </div>
                    @endif
                </aside>

                @if($order->user_id === auth()->id() && $order->isCompleted())
                    <aside class="panel panel-pad mb-4" id="review">
                        <div class="footer-title">Your review</div>

                        @if($order->review)
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                @include('partials.star-rating', ['rating' => $order->review->rating])
                                <span class="badge {{ $order->review->statusBadgeClass() }}">{{ $order->review->statusLabel() }}</span>
                            </div>
                            @if($order->review->comment)
                                <p class="small mb-2">{{ $order->review->comment }}</p>
                            @else
                                <p class="small text-muted-bp mb-2">You rated this order without written feedback.</p>
                            @endif
                            <div class="timeline-meta">Submitted {{ $order->review->created_at->format('M d, Y · h:i A') }}</div>
                            @if($order->review->status === \App\Models\Review::STATUS_PENDING)
                                <p class="small text-muted-bp mt-2 mb-0">Your review is waiting for moderation before it appears on the product pages.</p>
                            @elseif($order->review->status === \App\Models\Review::STATUS_HIDDEN)
                                <p class="small text-muted-bp mt-2 mb-0">This review is hidden from the storefront. Contact us if you have questions.</p>
                            @else
                                <p class="small text-muted-bp mt-2 mb-0">Published on the product pages for the items in this order.</p>
                            @endif
                        @else
                            <p class="small text-muted-bp">Tell us how this order went. Your rating helps other customers and our team.</p>
                            <form action="{{ route('orders.reviews.store', $order) }}" method="POST" data-submit-once>
                                @csrf
                                <fieldset class="review-rating-field">
                                    <legend class="form-label">Your rating</legend>
                                    <div class="review-rating-options">
                                        @for($star = 1; $star <= 5; $star++)
                                            <label class="review-rating-option" for="reviewRating{{ $star }}">
                                                <input id="reviewRating{{ $star }}" type="radio" name="rating" value="{{ $star }}" @checked((int) old('rating') === $star) required>
                                                <span><i class="bi bi-star-fill" aria-hidden="true"></i>{{ $star }}</span>
                                            </label>
                                        @endfor
                                    </div>
                                    @error('rating')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </fieldset>

                                <label class="form-label mt-3" for="reviewComment">Written feedback <span class="text-muted-bp">(optional)</span></label>
                                <textarea id="reviewComment" name="comment" class="form-control @error('comment') is-invalid @enderror" rows="3" maxlength="2000" placeholder="What did you enjoy? What could we improve?">{{ old('comment') }}</textarea>
                                @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror

                                <button class="btn btn-gold btn-sm w-100 mt-3" type="submit">Submit review</button>
                            </form>
                        @endif
                    </aside>
                @endif

                @if($order->requests->isNotEmpty())
                    <aside class="panel panel-pad mb-4">
                        <div class="footer-title">Support requests</div>
                        @foreach($order->requests as $orderRequest)
                            <div class="border-bottom border-secondary-subtle pb-2 mb-2">
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <span class="small text-cream">{{ $orderRequest->typeLabel() }}</span>
                                    <span class="badge {{ $orderRequest->statusBadgeClass() }}">{{ ucfirst($orderRequest->status) }}</span>
                                </div>
                                <div class="small text-muted-bp mt-1">{{ $orderRequest->reason }}</div>
                                @if($orderRequest->admin_note)
                                    <div class="small text-gold mt-1">Admin: {{ $orderRequest->admin_note }}</div>
                                @endif
                                <div class="timeline-meta mt-1">{{ $orderRequest->created_at->format('M d, Y · h:i A') }}</div>
                            </div>
                        @endforeach
                    </aside>
                @endif

                @if($order->canBeCancelledImmediately())
                    <aside class="panel panel-pad">
                        <div class="footer-title">Need to cancel?</div>
                        <p class="small text-muted-bp">This order has not started preparation, so you can cancel it right away. Verified payments are reviewed for a refund under our refund policy.</p>
                        <form action="{{ route('orders.cancel', $order) }}" method="POST" data-submit-once data-confirm="Cancel this order now?">
                            @csrf
                            <label class="form-label" for="cancelReason">Reason</label>
                            <textarea id="cancelReason" name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3" minlength="5" maxlength="500" required placeholder="Tell us why you need to cancel.">{{ old('reason') }}</textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <button class="btn btn-outline-gold btn-sm w-100 mt-3" type="submit">Cancel order</button>
                        </form>
                    </aside>
                @elseif($order->canBeRefundedByCustomer())
                    <aside class="panel panel-pad">
                        <div class="footer-title">Request a refund</div>
                        <p class="small text-muted-bp">This order is complete. If something was not right, tell us what happened and our team will review it.</p>
                        <form action="{{ route('orders.requests.store', $order) }}" method="POST" data-submit-once data-confirm="Submit a refund request for this order?">
                            @csrf
                            <input type="hidden" name="type" value="refund">
                            <label class="form-label" for="refundReason">Reason</label>
                            <textarea id="refundReason" name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3" minlength="10" maxlength="1000" required placeholder="Tell us what went wrong.">{{ old('type') === 'refund' ? old('reason') : '' }}</textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <button class="btn btn-outline-gold btn-sm w-100 mt-3" type="submit">Request refund</button>
                        </form>
                    </aside>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

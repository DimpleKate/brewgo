@extends('layouts.app')

@section('title', 'Checkout | '.$site['brand_name'])

@php
    $advanceRequired = $orderSettings['pickup_advance_payment_required'] === '1';
    $depositEnabled = $orderSettings['pickup_deposit_enabled'] === '1';
    $depositType = $orderSettings['pickup_deposit_type'] ?? 'percent';
    $depositValue = (float) ($orderSettings['pickup_deposit_value'] ?? 0);
    $depositLabel = $depositEnabled
        ? ($depositType === 'fixed' ? '₱'.number_format($depositValue, 2).' fixed deposit' : number_format($depositValue, 0).'% deposit')
        : 'full advance payment';
@endphp

@section('content')
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Final touches</span>
        <h1>Checkout, <span class="gold-text">your way.</span></h1>
        <p class="section-subtitle mb-0">Choose dine-in or pickup, review our order policies, and place your order.</p>
    </div>
</section>

<section class="section-pad pt-4">
    <div class="container">
        <form action="{{ route('checkout.store') }}" method="POST" enctype="multipart/form-data" data-service-form data-submit-once>
            @csrf
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="panel panel-pad mb-4">
                        <div class="footer-title">How would you like your order?</div>
                        <div class="service-grid">
                            <label class="service-card">
                                <input type="radio" name="service_type" value="dine_in" @checked(old('service_type', 'dine_in') === 'dine_in')>
                                <span class="service-body">
                                    <i class="bi bi-shop"></i>
                                    <strong>Dine-in</strong>
                                    <small>Order online and pay at the cashier before we prepare it.</small>
                                </span>
                            </label>
                            <label class="service-card">
                                <input type="radio" name="service_type" value="pickup" @checked(old('service_type') === 'pickup')>
                                <span class="service-body">
                                    <i class="bi bi-bag-check"></i>
                                    <strong>Pickup</strong>
                                    <small>Choose a pickup time. Advance payment is verified before preparation.</small>
                                </span>
                            </label>
                        </div>

                        <div class="mt-4 {{ old('service_type', 'dine_in') === 'dine_in' ? '' : 'd-none' }}" data-service-panel="dine_in">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="peopleCount">Number of people</label>
                                    <input id="peopleCount"
                                           type="number"
                                           name="people_count"
                                           class="form-control @error('people_count') is-invalid @enderror"
                                           value="{{ old('people_count', 1) }}"
                                           min="1"
                                           max="20">
                                    @error('people_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="dineInPaymentMethod">How will you pay at the cashier?</label>
                                    <select id="dineInPaymentMethod" name="payment_method" class="form-control @error('payment_method') is-invalid @enderror" required>
                                        <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                                        <option value="qr" @selected(old('payment_method') === 'qr')>GCash / QR</option>
                                    </select>
                                    @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="promo-hint mt-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Your order stays <strong>Pending Payment</strong> until the cashier verifies your cash or QR payment. It will not enter the preparation queue before then.
                            </div>
                        </div>

                        <div class="mt-4 {{ old('service_type') === 'pickup' ? '' : 'd-none' }}" data-service-panel="pickup">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="pickupName">Pickup name</label>
                                    <input id="pickupName"
                                           type="text"
                                           name="pickup_name"
                                           class="form-control @error('pickup_name') is-invalid @enderror"
                                           value="{{ old('pickup_name', $user->name) }}"
                                           maxlength="80"
                                           autocomplete="name">
                                    @error('pickup_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="pickupPhone">Contact number</label>
                                    <input id="pickupPhone"
                                           type="tel"
                                           name="pickup_phone"
                                           class="form-control @error('pickup_phone') is-invalid @enderror"
                                           value="{{ old('pickup_phone', $user->phone) }}"
                                           maxlength="20"
                                           autocomplete="tel"
                                           placeholder="0917 123 4567">
                                    @error('pickup_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="pickupAt">Pickup time</label>
                                    <input id="pickupAt"
                                           type="datetime-local"
                                           name="pickup_at"
                                           class="form-control @error('pickup_at') is-invalid @enderror"
                                           value="{{ old('pickup_at') }}"
                                           min="{{ $minPickupAt->format('Y-m-d\TH:i') }}"
                                           max="{{ $maxPickupAt->format('Y-m-d\TH:i') }}"
                                           required>
                                    @error('pickup_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text">Choose at least {{ $orderSettings['pickup_min_notice_minutes'] }} minutes from now. We start preparing about {{ $orderSettings['pickup_prep_minutes'] }} minutes before.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="pickup">Pickup deadline</label>
                                    <input id="pickup" type="text" class="form-control" value="{{ $orderSettings['pickup_deadline_minutes'] }} minutes after ready" disabled>
                                    <div class="form-text">Uncollected orders are flagged Overdue after this grace period.</div>
                                </div>
                            </div>

                            @if($advanceRequired)
                                <div class="payment-qr-panel mt-3">
                                    <div class="payment-qr-media">
                                        @if(! empty($orderSettings['payment_qr_code']))
                                            <img src="{{ asset($orderSettings['payment_qr_code']) }}"
                                                 alt="GCash payment QR code"
                                                 class="payment-qr-image">
                                        @else
                                            <div class="payment-qr-missing">
                                                <i class="bi bi-qr-code"></i>
                                                <span>QR not set up</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="payment-qr-copy">
                                        <div class="text-gold fw-semibold mb-1">Advance payment required</div>
                                        <div class="payment-qr-amount">₱{{ number_format($estimatedAdvance, 2) }}</div>
                                        <div class="small text-muted-bp">{{ $depositLabel }} · the final amount is confirmed after any promotion is applied.</div>
                                        @if(filled($orderSettings['payment_qr_instructions'] ?? null))
                                            <div class="small mt-2">{{ $orderSettings['payment_qr_instructions'] }}</div>
                                        @endif
                                        @if(empty($orderSettings['payment_qr_code']))
                                            <div class="small text-warning mt-2">The payment QR code has not been uploaded yet. Please contact the store before paying.</div>
                                        @endif
                                    </div>
                                </div>
                                <input type="hidden" name="payment_method" value="qr">
                                <div class="row g-3 mt-1">
                                    <div class="col-md-6">
                                        <label class="form-label" for="paymentReference">GCash / QR reference number</label>
                                        <input id="paymentReference"
                                               type="text"
                                               name="payment_reference"
                                               class="form-control @error('payment_reference') is-invalid @enderror"
                                               value="{{ old('payment_reference') }}"
                                               maxlength="80"
                                               placeholder="e.g. 1234567890123"
                                               required>
                                        @error('payment_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="paymentProof">Proof of payment</label>
                                        <input id="paymentProof"
                                               type="file"
                                               name="payment_proof"
                                               accept="image/png,image/jpeg,image/webp"
                                               class="form-control @error('payment_proof') is-invalid @enderror"
                                               required>
                                        @error('payment_proof')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        <div class="form-text">Upload a screenshot or photo of your GCash confirmation (max 4 MB).</div>
                                    </div>
                                </div>
                                <div class="form-text mt-2">Your order remains Pending Payment until staff verify your payment and proof. It will never be marked paid automatically.</div>
                            @else
                                <div class="mt-3">
                                    <label class="form-label" for="pickupPaymentMethod">Payment method</label>
                                    <select id="pickupPaymentMethod" name="payment_method" class="form-control @error('payment_method') is-invalid @enderror" required>
                                        <option value="qr" @selected(old('payment_method', 'qr') === 'qr')>GCash / QR</option>
                                        <option value="cash" @selected(old('payment_method') === 'cash')>Cash at pickup</option>
                                    </select>
                                    @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text">No advance payment is required. Please settle when you collect your order.</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="panel panel-pad mb-4">
                        <div class="footer-title">Order notes <span class="text-muted-bp fw-normal text-lowercase">(optional)</span></div>
                        <label class="form-label visually-hidden" for="notes">Notes for the kitchen</label>
                        <textarea id="notes"
                                  name="notes"
                                  class="form-control @error('notes') is-invalid @enderror"
                                  rows="4"
                                  maxlength="500"
                                  placeholder="Allergies, extra napkins, or a note for the team.">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Please keep notes under 500 characters.</div>
                    </div>

                    <div class="panel panel-pad">
                        <div class="footer-title">Payment, cancellation &amp; pickup policies</div>
                        <div class="small text-muted-bp">
                            <div class="mb-3">
                                <div class="text-gold fw-semibold mb-1"><i class="bi bi-credit-card me-1"></i>Payment</div>
                                {{ $policies['payment'] }}
                            </div>
                            <div class="mb-3">
                                <div class="text-gold fw-semibold mb-1"><i class="bi bi-x-circle me-1"></i>Cancellation</div>
                                {{ $policies['cancellation'] }}
                            </div>
                            <div class="mb-3">
                                <div class="text-gold fw-semibold mb-1"><i class="bi bi-arrow-counterclockwise me-1"></i>Refund</div>
                                {{ $policies['refund'] }}
                            </div>
                            <div>
                                <div class="text-gold fw-semibold mb-1"><i class="bi bi-alarm me-1"></i>Unclaimed orders</div>
                                {{ $policies['unclaimed'] }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <aside class="panel panel-pad sticky-summary">
                        <div class="footer-title">Your order</div>

                        <div class="mb-3">
                            @foreach($cart->items as $item)
                                @php
                                    $selectedOptions = $item->product->options->whereIn('id', $item->options ?? []);
                                    $selectedAddons = $item->product->addons->whereIn('id', $item->addons ?? []);
                                @endphp
                                <div class="d-flex align-items-start justify-content-between gap-2 py-2">
                                    <div class="small">
                                        <div class="text-cream">{{ $item->quantity }}× {{ $item->product->name }}</div>
                                        @if($selectedOptions->isNotEmpty() || $selectedAddons->isNotEmpty())
                                            <div class="text-muted-bp" style="font-size: 0.65rem;">
                                                {{ $selectedOptions->pluck('name')->merge($selectedAddons->pluck('name'))->join(', ') }}
                                            </div>
                                        @endif
                                        @if($item->note)
                                            <div class="text-gold" style="font-size: 0.65rem;">
                                                Note: {{ $item->note }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-cream small text-nowrap">₱{{ number_format($item->line_total, 2) }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span class="text-cream">₱{{ number_format($cart->subtotal, 2) }}</span>
                        </div>

                        <div class="mt-3">
                            <label class="form-label" for="promotionCode">Promotion code</label>
                            <input id="promotionCode"
                                   type="text"
                                   name="promotion_code"
                                   class="form-control text-uppercase @error('promotion_code') is-invalid @enderror"
                                   value="{{ old('promotion_code') }}"
                                   maxlength="40"
                                   placeholder="Enter code"
                                   list="promotionCodes"
                                   autocomplete="off">
                            @error('promotion_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if($promotions->isNotEmpty())
                                <datalist id="promotionCodes">
                                    @foreach($promotions as $promotion)
                                        @if($promotion->code)
                                            <option value="{{ $promotion->code }}">{{ $promotion->title }}</option>
                                        @endif
                                    @endforeach
                                </datalist>
                                <div class="promo-hint mt-2">
                                    <div class="text-gold fw-semibold mb-1">Available offers</div>
                                    @foreach($promotions as $promotion)
                                        <div>{{ $promotion->code }} — {{ $promotion->title }}@if($promotion->minimum_spend) (min. ₱{{ number_format((float) $promotion->minimum_spend, 2) }})@endif</div>
                                    @endforeach
                                </div>
                            @endif
                            <div class="form-text">Valid codes are verified when you place the order.</div>
                        </div>

                        <div class="summary-total mt-3">
                            <span>Estimated total</span>
                            <span class="text-gold">₱{{ number_format($cart->subtotal, 2) }}</span>
                        </div>

                        <div class="summary-row mt-2">
                            <span>Due now (pickup advance)</span>
                            <span class="text-cream">
                                @if($advanceRequired)
                                    ₱{{ number_format($estimatedAdvance, 2) }}
                                @else
                                    ₱0.00
                                @endif
                            </span>
                        </div>
                        @if($advanceRequired)
                            <div class="small text-muted-bp mt-1">Estimated using the {{ $depositLabel }} rule. Any remaining balance is paid at pickup.</div>
                        @endif

                        <p class="small text-muted-bp mt-3 mb-3">Promotion discounts are applied server-side. The final total and payment status appear on your order confirmation and digital receipt.</p>
                        <button class="btn btn-gold w-100" type="submit">Place order <i class="bi bi-check2-circle ms-1"></i></button>
                        <div class="text-center mt-3">
                            <a class="small text-muted-bp" href="{{ route('cart.index') }}"><i class="bi bi-arrow-left me-1"></i>Back to cart</a>
                        </div>
                    </aside>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

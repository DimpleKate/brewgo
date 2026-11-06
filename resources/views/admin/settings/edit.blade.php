@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
<div class="admin-page-head">
    <div>
        <div class="admin-page-eyebrow">System</div>
        <h1 class="admin-page-title">Settings</h1>
        <p class="admin-page-subtitle text-muted-bp">Update the storefront identity, contact details, and the admin account that manages this console.</p>
    </div>
    <div class="admin-head-actions">
        <a class="btn btn-soft btn-sm" href="{{ route('admin.dashboard') }}"><i class="bi bi-arrow-left me-1"></i>Back to dashboard</a>
        <button class="btn btn-gold btn-sm" type="submit" form="adminSettingsForm"><i class="bi bi-check2 me-1"></i>Save settings</button>
    </div>
</div>

<form id="adminSettingsForm" action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" data-submit-once>
    @csrf
    @method('PUT')

    <div class="row g-3">
        <div class="col-xl-7 d-flex flex-column gap-3">
            <div class="admin-card">
                <div class="admin-card-head">
                    <div>
                        <span class="admin-card-title">Storefront details</span>
                        <span class="admin-card-note">Shown across the public site, menus, and footer.</span>
                    </div>
                    <span class="admin-stat-icon"><i class="bi bi-shop"></i></span>
                </div>

                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label" for="brand_name">Brand name</label>
                        <input id="brand_name" type="text" name="brand_name" value="{{ old('brand_name', $content['brand_name'] ?? '') }}" class="form-control @error('brand_name') is-invalid @enderror" maxlength="80" required>
                        @error('brand_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="brand_tagline">Brand tagline</label>
                        <input id="brand_tagline" type="text" name="brand_tagline" value="{{ old('brand_tagline', $content['brand_tagline'] ?? '') }}" class="form-control @error('brand_tagline') is-invalid @enderror" maxlength="100">
                        @error('brand_tagline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact_phone">Contact phone</label>
                        <input id="contact_phone" type="tel" name="contact_phone" value="{{ old('contact_phone', $content['contact_phone'] ?? '') }}" class="form-control @error('contact_phone') is-invalid @enderror" maxlength="50">
                        @error('contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Leave blank to hide phone links on the storefront.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact_email">Contact email</label>
                        <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email', $content['contact_email'] ?? '') }}" class="form-control @error('contact_email') is-invalid @enderror" maxlength="150">
                        @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Used for storefront contact actions and customer replies.</div>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-head">
                    <div>
                        <span class="admin-card-title">Payment QR code</span>
                        <span class="admin-card-note">Shown on pickup checkout for GCash / QR payments.</span>
                    </div>
                    <span class="admin-stat-icon"><i class="bi bi-qr-code"></i></span>
                </div>

                <div class="row g-3 align-items-start">
                    <div class="col-sm-4 col-md-3">
                        <div class="qr-preview">
                            <img src="{{ $qrCodeUrl ?? '' }}" alt="Saved payment QR code" class="qr-preview-image {{ $qrCodeUrl ? '' : 'd-none' }}" data-qr-preview-image>
                            <div class="qr-preview-empty {{ $qrCodeUrl ? 'd-none' : '' }}" data-qr-preview-empty>
                                <i class="bi bi-qr-code"></i>
                                <span>No QR yet</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-8 col-md-9">
                        <label class="form-label" for="paymentQrCode">Upload or replace QR code</label>
                        <input id="paymentQrCode" type="file" name="payment_qr_code" accept="image/png,image/jpeg,image/webp" class="form-control @error('payment_qr_code') is-invalid @enderror" data-qr-preview-input>
                        @error('payment_qr_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">PNG, JPG, or WebP · up to 4 MB. A square image works best.</div>
                        @if($qrCodeUrl)
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="removePaymentQrCode" name="remove_payment_qr_code" value="1">
                                <label class="form-check-label" for="removePaymentQrCode">Remove the saved QR code</label>
                            </div>
                        @endif

                        <label class="form-label mt-3" for="paymentQrInstructions">Payment instructions</label>
                        <textarea id="paymentQrInstructions" name="payment_qr_instructions" rows="2" maxlength="1000" class="form-control @error('payment_qr_instructions') is-invalid @enderror">{{ old('payment_qr_instructions', $orderSettings['payment_qr_instructions'] ?? '') }}</textarea>
                        @error('payment_qr_instructions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Displayed beside the QR code on pickup checkout.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5 d-flex flex-column gap-3">
            <div class="admin-card">
                <div class="admin-card-head">
                    <div>
                        <span class="admin-card-title">Admin account</span>
                        <span class="admin-card-note">Signed in as {{ $admin->email }}</span>
                    </div>
                    <span class="avatar-circle">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Full name</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $admin->name) }}" class="form-control @error('name') is-invalid @enderror" maxlength="255" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $admin->email) }}" class="form-control @error('email') is-invalid @enderror" maxlength="255" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-head">
                    <div>
                        <span class="admin-card-title">Change password</span>
                        <span class="admin-card-note">Optional · minimum 8 characters</span>
                    </div>
                    <span class="admin-stat-icon"><i class="bi bi-key"></i></span>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="current_password">Current password</label>
                        <div class="password-field">
                            <input id="current_password" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password">
                            @include('partials.password-toggle', ['target' => 'current_password'])
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="password">New password</label>
                        <div class="password-field">
                            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                            @include('partials.password-toggle', ['target' => 'password'])
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="password_confirmation">Confirm new password</label>
                        <div class="password-field">
                            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                            @include('partials.password-toggle', ['target' => 'password_confirmation'])
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="admin-inline-note">
                            <i class="bi bi-info-circle"></i> Changing the account email changes the sign-in address for this admin console. Leave the password fields blank to keep the current password.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="admin-card">
                <div class="admin-card-head">
                    <div>
                        <span class="admin-card-title">Pickup protection &amp; policies</span>
                        <span class="admin-card-note">Advance payment, deposit, preparation, and pickup deadlines.</span>
                    </div>
                    <span class="admin-stat-icon"><i class="bi bi-shield-check"></i></span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="hidden" name="pickup_advance_payment_required" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="pickupAdvanceRequired" name="pickup_advance_payment_required" value="1" @checked(old('pickup_advance_payment_required', $orderSettings['pickup_advance_payment_required'] ?? '0') === '1')>
                            <label class="form-check-label" for="pickupAdvanceRequired">Require advance payment before preparing pickup orders</label>
                        </div>
                        <div class="form-text">When enabled, pickup customers must submit a GCash / QR reference that staff verify before preparation.</div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="hidden" name="pickup_deposit_enabled" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="pickupDepositEnabled" name="pickup_deposit_enabled" value="1" @checked(old('pickup_deposit_enabled', $orderSettings['pickup_deposit_enabled'] ?? '0') === '1')>
                            <label class="form-check-label" for="pickupDepositEnabled">Allow a partial deposit instead of the full amount</label>
                        </div>
                        <div class="form-text">A verified deposit is enough to enter the preparation queue. The remaining balance is recorded on the order.</div>
                    </div>

                    <div class="col-md-4 col-xl-2">
                        <label class="form-label" for="pickupDepositType">Deposit type</label>
                        <select id="pickupDepositType" name="pickup_deposit_type" class="form-select @error('pickup_deposit_type') is-invalid @enderror">
                            <option value="percent" @selected(old('pickup_deposit_type', $orderSettings['pickup_deposit_type'] ?? 'percent') === 'percent')>Percentage of total</option>
                            <option value="fixed" @selected(old('pickup_deposit_type', $orderSettings['pickup_deposit_type'] ?? 'percent') === 'fixed')>Fixed amount</option>
                        </select>
                        @error('pickup_deposit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 col-xl-2">
                        <label class="form-label" for="pickupDepositValue">Deposit value</label>
                        <input id="pickupDepositValue" type="number" step="0.01" min="0" name="pickup_deposit_value" value="{{ old('pickup_deposit_value', $orderSettings['pickup_deposit_value'] ?? 100) }}" class="form-control @error('pickup_deposit_value') is-invalid @enderror" required>
                        @error('pickup_deposit_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Percentage (0–100) or a fixed peso amount.</div>
                    </div>

                    <div class="col-md-4 col-xl-2">
                        <label class="form-label" for="pickupPrepMinutes">Prep starts before pickup</label>
                        <input id="pickupPrepMinutes" type="number" min="0" max="240" name="pickup_prep_minutes" value="{{ old('pickup_prep_minutes', $orderSettings['pickup_prep_minutes'] ?? 20) }}" class="form-control @error('pickup_prep_minutes') is-invalid @enderror" required>
                        @error('pickup_prep_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Minutes before the pickup time.</div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label class="form-label" for="pickupDeadlineMinutes">Pickup deadline after ready</label>
                        <input id="pickupDeadlineMinutes" type="number" min="1" max="720" name="pickup_deadline_minutes" value="{{ old('pickup_deadline_minutes', $orderSettings['pickup_deadline_minutes'] ?? 30) }}" class="form-control @error('pickup_deadline_minutes') is-invalid @enderror" required>
                        @error('pickup_deadline_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Minutes before an uncollected order is Overdue.</div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label class="form-label" for="pickupMinNoticeMinutes">Minimum pickup notice</label>
                        <input id="pickupMinNoticeMinutes" type="number" min="0" max="10080" name="pickup_min_notice_minutes" value="{{ old('pickup_min_notice_minutes', $orderSettings['pickup_min_notice_minutes'] ?? 30) }}" class="form-control @error('pickup_min_notice_minutes') is-invalid @enderror" required>
                        @error('pickup_min_notice_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Earliest pickup time a customer can choose.</div>
                    </div>

                    <div class="col-12"><hr class="border-secondary-subtle my-1"></div>

                    <div class="col-md-6">
                        <label class="form-label" for="orderPolicyPayment">Payment policy</label>
                        <textarea id="orderPolicyPayment" name="order_policy_payment" rows="2" maxlength="1000" class="form-control @error('order_policy_payment') is-invalid @enderror" required>{{ old('order_policy_payment', $orderSettings['order_policy_payment'] ?? '') }}</textarea>
                        @error('order_policy_payment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="orderPolicyCancellation">Cancellation policy</label>
                        <textarea id="orderPolicyCancellation" name="order_policy_cancellation" rows="2" maxlength="1000" class="form-control @error('order_policy_cancellation') is-invalid @enderror" required>{{ old('order_policy_cancellation', $orderSettings['order_policy_cancellation'] ?? '') }}</textarea>
                        @error('order_policy_cancellation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="orderPolicyRefund">Refund policy</label>
                        <textarea id="orderPolicyRefund" name="order_policy_refund" rows="2" maxlength="1000" class="form-control @error('order_policy_refund') is-invalid @enderror" required>{{ old('order_policy_refund', $orderSettings['order_policy_refund'] ?? '') }}</textarea>
                        @error('order_policy_refund')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="orderPolicyUnclaimed">Unclaimed-order policy</label>
                        <textarea id="orderPolicyUnclaimed" name="order_policy_unclaimed" rows="2" maxlength="1000" class="form-control @error('order_policy_unclaimed') is-invalid @enderror" required>{{ old('order_policy_unclaimed', $orderSettings['order_policy_unclaimed'] ?? '') }}</textarea>
                        @error('order_policy_unclaimed')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="settings-actions">
                <button class="btn btn-gold" type="submit"><i class="bi bi-check2 me-1"></i>Save settings</button>
            </div>
        </div>
    </div>
</form>
@endsection

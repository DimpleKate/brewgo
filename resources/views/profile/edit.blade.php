@extends('layouts.app')

@section('title', 'Your Profile | '.$site['brand_name'])

@section('content')
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Your account</span>
        <h1>Profile &amp; <span class="gold-text">preferences.</span></h1>
        <p class="section-subtitle mb-0">Keep your contact details current so pickup orders reach you smoothly.</p>
    </div>
</section>

<section class="section-pad pt-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <aside class="panel panel-pad">
                    <div class="profile-avatar-head">
                        <div data-avatar-preview>
                            @include('partials.avatar', ['user' => $user, 'size' => 'xl'])
                        </div>
                        <div class="min-w-0">
                            <div class="text-cream fw-semibold">{{ $user->name }}</div>
                            <div class="small text-muted-bp">{{ $user->email }}</div>
                            <div class="small text-gold">{{ $user->isAdmin() ? 'Administrator' : 'Customer' }}</div>
                        </div>
                    </div>

                    <form class="profile-avatar-form mt-3" action="{{ route('profile.avatar.update') }}" method="POST" enctype="multipart/form-data" data-submit-once>
                        @csrf
                        @method('PUT')

                        <label class="form-label" for="avatar">Profile picture</label>
                        <input id="avatar"
                               type="file"
                               name="avatar"
                               accept=".jpg,.jpeg,.png,.webp"
                               class="form-control form-control-sm @error('avatar') is-invalid @enderror"
                               data-avatar-input
                               required>
                        @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">JPG, JPEG, PNG, or WebP · max 5 MB.</div>

                        <button class="btn btn-gold btn-sm w-100 mt-2" type="submit">
                            <i class="bi bi-upload me-1"></i>{{ $user->avatar_path ? 'Replace picture' : 'Upload picture' }}
                        </button>
                    </form>

                    @if($user->avatar_path)
                        <form action="{{ route('profile.avatar.destroy') }}" method="POST" class="mt-2" data-confirm="Remove your profile picture?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-soft btn-sm w-100 text-danger" type="submit">
                                <i class="bi bi-trash3 me-1"></i>Remove picture
                            </button>
                        </form>
                    @endif

                    <div class="gold-rule my-3"></div>
                    <div class="d-grid gap-2">
                        <a class="footer-link" href="{{ route('orders.index') }}"><i class="bi bi-receipt me-2"></i>My orders</a>
                        <a class="footer-link" href="{{ route('cart.index') }}"><i class="bi bi-bag me-2"></i>My cart</a>
                        <a class="footer-link" href="{{ route('menu.index') }}"><i class="bi bi-cup-hot me-2"></i>Browse menu</a>
                        @if($user->isAdmin())
                            <a class="footer-link" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Admin dashboard</a>
                        @endif
                    </div>
                    <div class="gold-rule my-3"></div>
                    <div class="small text-muted-bp">
                        Member since {{ $user->created_at->format('M Y') }}
                    </div>
                </aside>
            </div>

            <div class="col-lg-8">
                <div class="panel panel-pad">
                    <div class="footer-title">Personal details</div>
                    <form action="{{ route('profile.update') }}" method="POST" data-submit-once>
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Full name</label>
                                <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required maxlength="100" autocomplete="name">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email address</label>
                                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone</label>
                                <input id="phone" type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" maxlength="30" autocomplete="tel" placeholder="0917 123 4567">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="address">Address <span class="text-muted-bp">(optional)</span></label>
                                <input id="address" type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $user->address) }}" maxlength="500" autocomplete="street-address">
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="gold-rule my-4"></div>
                        <div class="footer-title">Change password <span class="text-muted-bp fw-normal text-lowercase">(optional)</span></div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="currentPassword">Current password</label>
                                <div class="password-field">
                                    <input id="currentPassword" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password">
                                    @include('partials.password-toggle', ['target' => 'currentPassword'])
                                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="newPassword">New password</label>
                                <div class="password-field">
                                    <input id="newPassword" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                                    @include('partials.password-toggle', ['target' => 'newPassword'])
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="newPasswordConfirmation">Confirm new password</label>
                                <div class="password-field">
                                    <input id="newPasswordConfirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                                    @include('partials.password-toggle', ['target' => 'newPasswordConfirmation'])
                                </div>
                            </div>
                        </div>
                        <div class="form-text mt-2">Use at least 8 characters with upper and lowercase letters and numbers.</div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4">
                            <a class="small text-muted-bp" href="{{ route('home') }}"><i class="bi bi-arrow-left me-1"></i>Back to home</a>
                            <button class="btn btn-gold" type="submit">Save changes <i class="bi bi-check2 ms-1"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-6">
                <div class="panel panel-pad h-100">
                    <div class="footer-title">Download your data</div>
                    <p class="small text-muted-bp">Export your account details, order history, payments, receipts, refunds, and support requests as a JSON file.</p>
                    <a class="btn btn-soft btn-sm" href="{{ route('account.export') }}">
                        <i class="bi bi-download me-1"></i>Download my data
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="panel panel-pad h-100">
                    <div class="footer-title text-danger">Delete account</div>
                    <p class="small text-muted-bp">Your personal details are anonymised and you are signed out. Order records are retained for accounting and tax purposes.</p>
                    <form action="{{ route('account.destroy') }}" method="POST" data-submit-once data-confirm="Delete your account? This cannot be undone.">
                        @csrf
                        @method('DELETE')

                        <label class="form-label" for="deletePassword">Current password</label>
                        <input id="deletePassword" type="password" name="password" class="form-control form-control-sm @error('password') is-invalid @enderror" autocomplete="current-password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        <label class="form-label mt-2" for="deleteConfirmation">Type DELETE to confirm</label>
                        <input id="deleteConfirmation" type="text" name="confirmation" class="form-control form-control-sm @error('confirmation') is-invalid @enderror" required pattern="DELETE">
                        @error('confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        <button class="btn btn-outline-danger btn-sm mt-3" type="submit">
                            <i class="bi bi-person-x me-1"></i>Delete my account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

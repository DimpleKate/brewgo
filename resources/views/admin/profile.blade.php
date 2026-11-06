@extends('layouts.admin')

@section('title', 'My profile')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">My profile</h1>
        <p class="admin-page-subtitle text-muted-bp">Update the administrator details shown across the console.</p>
    </div>
    <div class="admin-head-actions">
        <a class="btn btn-soft btn-sm" href="{{ route('admin.security.edit') }}"><i class="bi bi-shield-lock me-1"></i>Security</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="admin-card">
            <div class="footer-title">Profile picture</div>
            <div class="d-flex align-items-center gap-3 mb-3">
                @include('partials.avatar', ['user' => $user, 'size' => 'xl'])
                <div>
                    <div class="text-cream fw-semibold">{{ $user->name }}</div>
                    <div class="small text-muted-bp">{{ $user->email }}</div>
                    <div class="small text-gold">{{ $user->roleLabel() }}</div>
                </div>
            </div>

            <form action="{{ route('admin.profile.avatar.update') }}" method="POST" enctype="multipart/form-data" data-submit-once>
                @csrf
                <label class="form-label" for="avatar">New picture</label>
                <input id="avatar" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp" class="form-control form-control-sm @error('avatar') is-invalid @enderror" required>
                @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">JPG, JPEG, PNG, or WebP · max 5 MB.</div>
                <button class="btn btn-gold btn-sm w-100 mt-2" type="submit">Upload picture</button>
            </form>

            <div class="gold-rule my-3"></div>
            <div class="small text-muted-bp">Role: {{ $user->roleLabel() }}</div>
            <div class="small text-muted-bp">Last sign-in: {{ $user->updated_at?->format('M d, Y · h:i A') }}</div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="admin-card">
            <div class="footer-title">Administrator details</div>
            <form action="{{ route('admin.profile.update') }}" method="POST" data-submit-once>
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Full name</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="100" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" class="form-control @error('email') is-invalid @enderror" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Phone</label>
                        <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30" class="form-control @error('phone') is-invalid @enderror">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="gold-rule my-4"></div>
                <div class="footer-title">Change password <span class="text-muted-bp fw-normal text-lowercase">(optional)</span></div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="currentPassword">Current password</label>
                        <input id="currentPassword" type="password" name="current_password" autocomplete="current-password" class="form-control @error('current_password') is-invalid @enderror">
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="newPassword">New password</label>
                        <input id="newPassword" type="password" name="password" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="newPasswordConfirmation">Confirm new password</label>
                        <input id="newPasswordConfirmation" type="password" name="password_confirmation" autocomplete="new-password" class="form-control">
                    </div>
                </div>
                <div class="form-text mt-2">Use at least 8 characters with upper and lowercase letters and numbers.</div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-soft" href="{{ route('admin.dashboard') }}">Cancel</a>
                    <button class="btn btn-gold" type="submit">Save profile</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.auth')

@section('title', 'Two-factor challenge | '.$site['brand_name'])

@section('content')
<div class="text-center mb-3">
    <h2 class="auth-card-title h5">Two-factor authentication</h2>
    <p class="auth-card-subtitle mb-0">Enter the 6-digit code from your authenticator app, or use a recovery code.</p>
</div>

@if($errors->any())
    <div class="alert alert-danger small">{{ $errors->first() }}</div>
@endif

<form action="{{ route('admin.two-factor.challenge.store') }}" method="POST" data-submit-once>
    @csrf

    <div class="mb-3" data-two-factor-code>
        <label class="form-label form-label--auth" for="code">Authentication code</label>
        <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" class="form-control form-control-auth" autofocus>
    </div>

    <div class="mb-3 d-none" data-two-factor-recovery>
        <label class="form-label form-label--auth" for="recovery_code">Recovery code</label>
        <input id="recovery_code" type="text" name="recovery_code" maxlength="40" class="form-control form-control-auth">
    </div>

    <button class="btn btn-auth w-100" type="submit">Continue</button>

    <div class="text-center mt-3">
        <button class="btn btn-link btn-sm p-0" type="button" data-two-factor-toggle>Use a recovery code instead</button>
    </div>
</form>

<div class="auth-footer-links">
    <div class="auth-footer-row">
        <a href="{{ route('admin.login') }}"><i class="bi bi-arrow-left me-1"></i>Back to admin sign in</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelector('[data-two-factor-toggle]')?.addEventListener('click', (event) => {
        const codeGroup = document.querySelector('[data-two-factor-code]');
        const recoveryGroup = document.querySelector('[data-two-factor-recovery]');
        const useRecovery = codeGroup.classList.contains('d-none');

        codeGroup.classList.toggle('d-none', !useRecovery);
        recoveryGroup.classList.toggle('d-none', useRecovery);
        event.currentTarget.textContent = useRecovery ? 'Use a recovery code instead' : 'Use an authenticator code instead';
    });
</script>
@endpush

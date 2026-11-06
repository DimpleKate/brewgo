@if(session('success'))
    <div class="alert alert-bp alert-dismissible fade show mt-3" role="alert" data-auto-dismiss>
        <i class="bi bi-check-circle me-2 text-gold"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-bp alert-danger alert-dismissible fade show mt-3" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-bp alert-danger alert-dismissible fade show mt-3" role="alert">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-2"></i>Please check the highlighted fields.</div>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

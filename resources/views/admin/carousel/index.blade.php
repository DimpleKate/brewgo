@extends('layouts.admin')

@section('title', 'Carousel')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Carousel</h1>
        <p class="admin-page-subtitle text-muted-bp">Upload images for the Brew &amp; Go homepage slider.</p>
    </div>
    <span class="badge text-bg-secondary align-self-start align-self-md-center">
        {{ $slides->count() }} {{ \Illuminate\Support\Str::plural('image', $slides->count()) }}
    </span>
</div>

<div class="admin-card mb-3">
    <div class="admin-card-head">
        <div>
            <span class="admin-card-title">Upload carousel images</span>
            <span class="admin-card-note">Choose one or multiple files at once. New images appear on the homepage slider immediately.</span>
        </div>
        <span class="admin-stat-icon"><i class="bi bi-cloud-arrow-up"></i></span>
    </div>

    <form action="{{ route('admin.carousel.store-many') }}" method="POST" enctype="multipart/form-data" data-carousel-bulk data-submit-once>
        @csrf

        <div class="carousel-upload-grid">
            <div>
                <label class="form-label" for="carouselImages">Images</label>
                <input id="carouselImages"
                       type="file"
                       name="images[]"
                       accept=".jpg,.jpeg,.png,.webp"
                       class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
                       data-carousel-input
                       multiple
                       required>
            </div>
            <div class="carousel-upload-action">
                <button class="btn btn-gold" type="submit"><i class="bi bi-upload me-1"></i>Upload images</button>
            </div>
        </div>

        @error('images')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('images.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

        <div class="form-text mt-2">JPG, JPEG, PNG, or WebP · up to 5 MB each · up to 20 images at a time.</div>

        <div class="carousel-preview-grid d-none" data-carousel-preview aria-live="polite"></div>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-head">
        <div>
            <span class="admin-card-title">Slider images</span>
            <span class="admin-card-note">Preview images full size, select several to delete, or remove a single image.</span>
        </div>
    </div>

    @if($slides->isEmpty())
        <div class="empty-state">
            <i class="bi bi-images"></i>
            <p class="mb-3">No carousel images yet.</p>
            <label class="btn btn-gold btn-sm mb-0" for="carouselImages">Upload the first images</label>
        </div>
    @else
        <form id="carouselBulkDelete"
              action="{{ route('admin.carousel.destroy-many') }}"
              method="POST"
              data-confirm="Delete the selected carousel images? This cannot be undone.">
            @csrf
            @method('DELETE')
        </form>

        <div class="carousel-toolbar">
            <label class="form-check mb-0">
                <input class="form-check-input" type="checkbox" data-carousel-select-all>
                <span class="form-check-label">Select all</span>
            </label>

            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted-bp" data-carousel-selected-count aria-live="polite">0 selected</span>
                <button class="btn btn-danger btn-sm" type="submit" form="carouselBulkDelete" data-carousel-bulk-submit disabled>
                    <i class="bi bi-trash3 me-1"></i>Delete selected
                </button>
            </div>
        </div>

        @if($errors->has('ids') || $errors->has('ids.*'))
            <div class="alert alert-danger py-2 px-3 small mb-3" role="alert">
                {{ $errors->first('ids') ?: $errors->first('ids.*') }}
            </div>
        @endif

        <div class="carousel-gallery">
            @foreach($slides as $slide)
                <article class="carousel-gallery-card">
                    <label class="carousel-gallery-select" title="Select this image">
                        <input class="form-check-input"
                               type="checkbox"
                               name="ids[]"
                               value="{{ $slide->id }}"
                               form="carouselBulkDelete"
                               data-carousel-select
                               aria-label="Select carousel image">
                    </label>

                    <a class="carousel-gallery-media"
                       href="{{ $slide->image_url }}"
                       target="_blank"
                       rel="noopener"
                       aria-label="Preview carousel image full size">
                        @if($slide->image_url)
                            <img src="{{ $slide->image_url }}" alt="{{ $slide->title ?: 'Brew & Go carousel image' }}" loading="lazy">
                        @else
                            <span class="carousel-gallery-fallback media-fallback"><i class="bi bi-image"></i></span>
                        @endif
                    </a>

                    <form action="{{ route('admin.carousel.destroy', $slide) }}"
                          method="POST"
                          class="carousel-gallery-delete"
                          data-confirm="Delete this carousel image? It will be removed from the homepage slider.">
                        @csrf
                        @method('DELETE')
                        <button class="carousel-gallery-delete-btn" type="submit" aria-label="Delete carousel image">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection

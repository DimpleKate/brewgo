@php($category = $category ?? null)

<div class="row g-3">
    <div class="col-md-7">
        <label class="form-label" for="name">Name</label>
        <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category?->name) }}" required maxlength="100">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-5">
        <label class="form-label" for="slug">Slug</label>
        <input id="slug" type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $category?->slug) }}" maxlength="120" placeholder="auto-generated">
        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" maxlength="1000">{{ old('description', $category?->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label class="form-label" for="image">Image path or URL</label>
        <input id="image" type="text" name="image" class="form-control @error('image') is-invalid @enderror" value="{{ old('image', $category?->image) }}" maxlength="500" placeholder="images/categories/your-image.jpg">
        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Use a path relative to <code>public/</code> or a full http(s) URL.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="sort_order">Sort order</label>
        <input id="sort_order" type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $category?->sort_order ?? 0) }}" min="0" max="999">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="image_file">Upload new image</label>
        <input id="image_file" type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp" class="form-control @error('image_file') is-invalid @enderror">
        @error('image_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">JPG, PNG, or WebP up to 4 MB. Uploading replaces the current image.</div>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input id="is_active" type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $category?->is_active ?? true))>
            <label class="form-check-label" for="is_active">Active and visible on the storefront</label>
        </div>
    </div>
</div>

@if($category?->image && $category?->image_url)
    <div class="mt-3">
        <div class="form-label">Current image</div>
        <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="admin-image-preview">
        <div class="form-check mt-2">
            <input id="remove_image" type="checkbox" name="remove_image" value="1" class="form-check-input" @checked(old('remove_image'))>
            <label class="form-check-label small" for="remove_image">Remove the current image</label>
        </div>
    </div>
@endif

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
    <a class="btn btn-soft" href="{{ route('admin.categories.index') }}">Cancel</a>
    <button class="btn btn-gold" type="submit">{{ $category ? 'Save category' : 'Create category' }} <i class="bi bi-check2 ms-1"></i></button>
</div>

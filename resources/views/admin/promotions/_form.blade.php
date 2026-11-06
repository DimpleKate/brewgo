@php
    $promotion = $promotion ?? null;
    $categories = $categories ?? collect();
@endphp

<div class="row g-3">
    <div class="col-md-7">
        <label class="form-label" for="title">Title</label>
        <input id="title" type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $promotion?->title) }}" required maxlength="120" placeholder="e.g. Welcome Offer">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-5">
        <label class="form-label" for="code">Code <span class="text-muted-bp">(optional)</span></label>
        <input id="code" type="text" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code', $promotion?->code) }}" maxlength="40" placeholder="WELCOME10">
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" maxlength="1000">{{ old('description', $promotion?->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="discount_type">Discount type</label>
        <select id="discount_type" name="discount_type" class="form-select @error('discount_type') is-invalid @enderror" required>
            <option value="percent" @selected(old('discount_type', $promotion?->discount_type ?? 'percent') === 'percent')>Percent (%)</option>
            <option value="fixed" @selected(old('discount_type', $promotion?->discount_type) === 'fixed')>Fixed amount (₱)</option>
        </select>
        @error('discount_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="discount_value">Discount value</label>
        <input id="discount_value" type="number" step="0.01" min="0" max="100000" name="discount_value" class="form-control @error('discount_value') is-invalid @enderror" value="{{ old('discount_value', $promotion?->discount_value) }}" required>
        @error('discount_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="minimum_spend">Minimum spend (₱)</label>
        <input id="minimum_spend" type="number" step="0.01" min="0" max="100000" name="minimum_spend" class="form-control @error('minimum_spend') is-invalid @enderror" value="{{ old('minimum_spend', $promotion?->minimum_spend ?? 0) }}">
        @error('minimum_spend')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="applies_to">Applies to</label>
        <select id="applies_to" name="applies_to" class="form-select @error('applies_to') is-invalid @enderror" required>
            <option value="all" @selected(old('applies_to', $promotion?->applies_to ?? 'all') === 'all')>Entire order</option>
            <option value="category" @selected(old('applies_to', $promotion?->applies_to) === 'category')>One category</option>
        </select>
        @error('applies_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="category_id">Category</label>
        <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror">
            <option value="">Select a category</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $promotion?->category_id) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Only used when the promotion applies to one category.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="max_discount">Maximum discount (₱)</label>
        <input id="max_discount" type="number" step="0.01" min="0" max="100000" name="max_discount" class="form-control @error('max_discount') is-invalid @enderror" value="{{ old('max_discount', $promotion?->max_discount) }}" placeholder="No cap">
        @error('max_discount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Optional cap for percentage discounts.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="usage_limit">Total usage limit</label>
        <input id="usage_limit" type="number" step="1" min="1" max="100000" name="usage_limit" class="form-control @error('usage_limit') is-invalid @enderror" value="{{ old('usage_limit', $promotion?->usage_limit) }}" placeholder="Unlimited">
        @error('usage_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Leave blank for unlimited redemptions.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="per_customer_limit">Per-customer limit</label>
        <input id="per_customer_limit" type="number" step="1" min="1" max="100000" name="per_customer_limit" class="form-control @error('per_customer_limit') is-invalid @enderror" value="{{ old('per_customer_limit', $promotion?->per_customer_limit) }}" placeholder="Unlimited">
        @error('per_customer_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">How many times one customer may redeem it.</div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="starts_at">Starts at</label>
        <input id="starts_at" type="datetime-local" name="starts_at" class="form-control @error('starts_at') is-invalid @enderror" value="{{ old('starts_at', $promotion?->starts_at?->format('Y-m-d\TH:i')) }}">
        @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="ends_at">Ends at</label>
        <input id="ends_at" type="datetime-local" name="ends_at" class="form-control @error('ends_at') is-invalid @enderror" value="{{ old('ends_at', $promotion?->ends_at?->format('Y-m-d\TH:i')) }}">
        @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="image">Image path or URL</label>
        <input id="image" type="text" name="image" class="form-control @error('image') is-invalid @enderror" value="{{ old('image', $promotion?->image) }}" maxlength="500" placeholder="images/promotions/your-image.jpg">
        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
            <input id="is_active" type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $promotion?->is_active ?? true))>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
</div>

@if($promotion?->image && $promotion?->image_url)
    <div class="mt-3">
        <div class="form-label">Current image</div>
        <img src="{{ $promotion->image_url }}" alt="{{ $promotion->title }}" class="admin-image-preview">
        <div class="form-check mt-2">
            <input id="remove_image" type="checkbox" name="remove_image" value="1" class="form-check-input" @checked(old('remove_image'))>
            <label class="form-check-label small" for="remove_image">Remove the current image</label>
        </div>
    </div>
@endif

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
    <a class="btn btn-soft" href="{{ route('admin.promotions.index') }}">Cancel</a>
    <button class="btn btn-gold" type="submit">{{ $promotion ? 'Save promotion' : 'Create promotion' }} <i class="bi bi-check2 ms-1"></i></button>
</div>

@php($addon = $addon ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="name">Add-on name</label>
        <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $addon?->name) }}" required maxlength="100" placeholder="e.g. Extra espresso shot">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="price">Price (₱)</label>
        <input id="price" type="number" step="0.01" min="0" max="10000" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $addon?->price) }}" required>
        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check pb-2">
            <input type="hidden" name="is_active" value="0">
            <input id="is_active" type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $addon?->is_active ?? true))>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
    <a class="btn btn-soft" href="{{ route('admin.addons.index') }}">Cancel</a>
    <button class="btn btn-gold" type="submit">{{ $addon ? 'Save add-on' : 'Create add-on' }} <i class="bi bi-check2 ms-1"></i></button>
</div>

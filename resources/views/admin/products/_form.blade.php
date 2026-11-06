@php
    $product = $product ?? null;
    $selectedAddonIds = collect(old('addons', $product?->addons->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all();
    $sizeOptionPrices = collect($product?->options ?? [])
        ->where('group_name', 'size')
        ->keyBy(fn ($option) => strtolower($option->name));
    $sizePriceValues = collect(['small', 'medium', 'large'])
        ->mapWithKeys(fn ($size) => [$size => old('size_prices.'.$size, $sizeOptionPrices->get($size)?->price)]);
@endphp

@if(! $product)
    <input type="hidden" name="submission_token" value="{{ old('submission_token', $formToken ?? '') }}">
@endif

<div class="row g-3">
    <div class="col-md-7">
        <label class="form-label" for="name">Product name</label>
        <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product?->name) }}" required maxlength="120">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-5">
        <label class="form-label" for="slug">Slug</label>
        <input id="slug" type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $product?->slug) }}" maxlength="140" placeholder="auto-generated">
        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-5">
        <label class="form-label" for="category_id">Category</label>
        <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
            <option value="">Choose a category</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $product?->category_id) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-7">
        <label class="form-label" for="image_file">Product picture</label>
        <input id="image_file" type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp" class="form-control @error('image_file') is-invalid @enderror">
        <div class="form-text text-muted-bp">JPG, PNG, or WebP. Maximum 4 MB. {{ $product ? 'Upload a new picture to replace the current one.' : 'Optional.' }} If the form needs corrections, you may need to select the picture again.</div>
        @error('image_file')<div class="invalid-feedback">{{ $message }}</div>@enderror

        <div class="mt-2 d-none" data-image-preview-wrap>
            <img alt="Selected product picture preview" class="admin-image-preview admin-image-preview-sm" data-image-preview>
        </div>

        @if($product?->image)
            <div class="d-flex align-items-center gap-2 mt-2">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="admin-image-preview admin-image-preview-sm">
                <div class="form-check mb-0">
                    <input id="remove_image" type="checkbox" name="remove_image" value="1" class="form-check-input" @checked(old('remove_image'))>
                    <label class="form-check-label small" for="remove_image">Remove current picture</label>
                </div>
            </div>
        @endif
    </div>

    <div class="col-12">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="4" required maxlength="2000">{{ old('description', $product?->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <div class="footer-title mt-2">Drink sizes</div>
        <p class="small text-muted-bp mb-2">Set the price for each cup size. Customers must choose one before adding the drink to their cart. These prices appear on the menu as soon as you save.</p>
        <div class="row g-3">
            @foreach(['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'] as $sizeKey => $sizeLabel)
                <div class="col-md-4">
                    <label class="form-label" for="size_price_{{ $sizeKey }}">{{ $sizeLabel }} price (₱)</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input id="size_price_{{ $sizeKey }}" type="number" step="0.01" min="0" max="100000" name="size_prices[{{ $sizeKey }}]" class="form-control @error('size_prices.'.$sizeKey) is-invalid @enderror" value="{{ $sizePriceValues->get($sizeKey) }}" required>
                        @error('size_prices.'.$sizeKey)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-12">
        <div class="footer-title mt-2">Available add-ons</div>
        @if($addons->isEmpty())
            <p class="small text-muted-bp">No add-ons exist yet. <a class="text-gold" href="{{ route('admin.addons.create') }}">Create one first</a>.</p>
        @else
            <div class="row g-2">
                @foreach($addons as $addon)
                    <div class="col-sm-6 col-lg-4">
                        <label class="choice-card d-block">
                            <input type="checkbox" name="addons[]" value="{{ $addon->id }}" @checked(in_array($addon->id, $selectedAddonIds, true))>
                            <span class="choice-body w-100 justify-content-between">
                                <span>{{ $addon->name }}</span>
                                <small>+₱{{ number_format((float) $addon->price, 2) }}</small>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="col-md-6">
        <div class="form-check">
            <input type="hidden" name="is_featured" value="0">
            <input id="is_featured" type="checkbox" name="is_featured" value="1" class="form-check-input" @checked(old('is_featured', $product?->is_featured ?? false))>
            <label class="form-check-label" for="is_featured">Feature in the homepage hero highlight</label>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input id="is_active" type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $product?->is_active ?? true))>
            <label class="form-check-label" for="is_active">Active and orderable</label>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
    <a class="btn btn-soft" href="{{ route('admin.products.index') }}">Cancel</a>
    <button class="btn btn-gold" type="submit">{{ $product ? 'Save product' : 'Create product' }} <i class="bi bi-check2 ms-1"></i></button>
</div>

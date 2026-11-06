@php
    $searchValue = old('search', $search ?? '');
@endphp

<form method="GET" action="{{ request()->url() }}" class="product-search mb-4" role="search" data-product-filter-form>
    @if(filled($category ?? null))
        <input type="hidden" name="category" value="{{ $category }}">
    @endif
    <div class="row g-3 align-items-end">
        <div class="col-lg-6">
            <label class="form-label" for="{{ $inputId }}">{{ $label }}</label>
            <div class="search-bp">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input id="{{ $inputId }}"
                       type="search"
                       name="search"
                       value="{{ $searchValue }}"
                       class="form-control"
                       placeholder="{{ $placeholder }}"
                       autocomplete="off"
                       data-product-search>
                <button type="button"
                        class="search-clear {{ filled($searchValue) ? '' : 'd-none' }}"
                        data-product-search-clear
                        aria-label="Clear search">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
</form>

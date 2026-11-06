@extends('layouts.admin')

@section('title', 'Products')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Products</h1>
        <p class="admin-page-subtitle text-muted-bp">Manage every drink, dish, price, and availability state.</p>
    </div>
    <a class="btn btn-gold btn-sm" href="{{ route('admin.products.create') }}"><i class="bi bi-plus-lg me-1"></i>New product</a>
</div>

<div class="admin-card mb-3">
    <form method="GET" action="{{ route('admin.products.index') }}" class="row g-2 align-items-end" data-live-filter-form>
        <div class="col-md-5">
            <label class="form-label" for="search">Search</label>
            <div class="search-bp">
                <i class="bi bi-search"></i>
                <input id="search" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by product name">
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="category">Category</label>
            <select id="category" name="category" class="form-select">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) request('category') === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        @if(request()->filled('search') || request()->filled('category'))
            <div class="col-auto">
                <a class="btn btn-soft btn-sm" href="{{ route('admin.products.index') }}">Reset</a>
            </div>
        @endif
    </form>
</div>

<div class="admin-card">
    @if($products->isEmpty())
        <div class="empty-state">
            <i class="bi bi-cup-hot"></i>
            <p class="mb-3">No products match this view.</p>
            <a class="btn btn-gold btn-sm" href="{{ route('admin.products.create') }}">Create a product</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Flags</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($product->image_url)
                                        <img class="admin-thumb" src="{{ $product->image_url }}" alt="{{ $product->name }}">
                                    @else
                                        <span class="admin-thumb admin-thumb-fallback media-fallback"><i class="bi bi-cup-hot"></i></span>
                                    @endif
                                    <div>
                                        <div class="text-cream">{{ $product->name }}</div>
                                        <div class="text-muted-bp admin-meta">{{ $product->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted-bp">{{ $product->category?->name }}</td>
                            <td class="text-gold">₱{{ number_format((float) $product->base_price, 2) }}</td>
                            <td>
                                @if($product->is_featured)
                                    <span class="badge text-bg-warning">Featured</span>
                                @endif
                            </td>
                            <td>
                                @if($product->is_active)
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-secondary">Hidden</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a class="btn btn-soft btn-sm" href="{{ route('admin.products.edit', $product) }}">Edit</a>
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" data-confirm="Delete this product? Existing order snapshots will be preserved.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link btn-sm text-danger p-1" type="submit" aria-label="Delete {{ $product->name }}"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $products->links() }}</div>
    @endif
</div>
@endsection

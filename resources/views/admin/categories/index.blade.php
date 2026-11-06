@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Categories</h1>
        <p class="admin-page-subtitle text-muted-bp">Organize the menu into categories. Active categories appear as filters on the customer menu.</p>
    </div>
    <a class="btn btn-gold btn-sm" href="{{ route('admin.categories.create') }}"><i class="bi bi-plus-lg me-1"></i>New category</a>
</div>

<div class="admin-card">
    @if($categories->isEmpty())
        <div class="empty-state">
            <i class="bi bi-tags"></i>
            <p class="mb-3">No categories yet.</p>
            <a class="btn btn-gold btn-sm" href="{{ route('admin.categories.create') }}">Create one</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Slug</th>
                        <th>Products</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($category->image_url)
                                        <img class="admin-thumb" src="{{ $category->image_url }}" alt="{{ $category->name }}">
                                    @else
                                        <span class="admin-thumb admin-thumb-fallback media-fallback"><i class="bi bi-cup-hot"></i></span>
                                    @endif
                                    <div>
                                        <div class="text-cream">{{ $category->name }}</div>
                                        <div class="text-muted-bp admin-meta">{{ \Illuminate\Support\Str::limit($category->description, 45) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted-bp">{{ $category->slug }}</td>
                            <td><span class="badge text-bg-secondary">{{ $category->products_count }}</span></td>
                            <td class="text-muted-bp">{{ $category->sort_order }}</td>
                            <td>
                                @if($category->is_active)
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-secondary">Hidden</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a class="btn btn-soft btn-sm" href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" data-confirm="Delete this category? This cannot be undone.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link btn-sm text-danger p-1" type="submit" aria-label="Delete {{ $category->name }}"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $categories->links() }}</div>
    @endif
</div>
@endsection

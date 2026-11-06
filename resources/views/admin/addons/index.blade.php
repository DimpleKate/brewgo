@extends('layouts.admin')

@section('title', 'Add-ons')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Add-ons</h1>
        <p class="admin-page-subtitle text-muted-bp">Paid extras customers can attach to eligible products.</p>
    </div>
    <a class="btn btn-gold btn-sm" href="{{ route('admin.addons.create') }}"><i class="bi bi-plus-lg me-1"></i>New add-on</a>
</div>

<div class="admin-card">
    @if($addons->isEmpty())
        <div class="empty-state">
            <i class="bi bi-plus-square"></i>
            <p class="mb-3">No add-ons yet.</p>
            <a class="btn btn-gold btn-sm" href="{{ route('admin.addons.create') }}">Create one</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr>
                        <th>Add-on</th>
                        <th>Price</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($addons as $addon)
                        <tr>
                            <td class="text-cream">{{ $addon->name }}</td>
                            <td class="text-gold">₱{{ number_format((float) $addon->price, 2) }}</td>
                            <td><span class="badge text-bg-secondary">{{ $addon->products_count }}</span></td>
                            <td>
                                @if($addon->is_active)
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a class="btn btn-soft btn-sm" href="{{ route('admin.addons.edit', $addon) }}">Edit</a>
                                    <form action="{{ route('admin.addons.destroy', $addon) }}" method="POST" data-confirm="Delete this add-on? It will be detached from products.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link btn-sm text-danger p-1" type="submit" aria-label="Delete {{ $addon->name }}"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $addons->links() }}</div>
    @endif
</div>
@endsection

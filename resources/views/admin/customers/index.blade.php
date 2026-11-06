@extends('layouts.admin')

@section('title', 'Customers')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Customers</h1>
        <p class="admin-page-subtitle text-muted-bp">View customer history and manage account access.</p>
    </div>
</div>

<div class="admin-card mb-3">
    <form method="GET" action="{{ route('admin.customers.index') }}" class="row g-2 align-items-end" data-live-filter-form>
        <div class="col-md-6">
            <label class="form-label" for="search">Search</label>
            <div class="search-bp">
                <i class="bi bi-search"></i>
                <input id="search" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, email, or phone">
            </div>
        </div>
        @if(request()->filled('search'))
            <div class="col-auto">
                <a class="btn btn-soft btn-sm" href="{{ route('admin.customers.index') }}">Reset</a>
            </div>
        @endif
    </form>
</div>

<div class="admin-card">
    @if($customers->isEmpty())
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <p class="mb-0">No customers match this view.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Orders</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @include('partials.avatar', ['user' => $customer, 'size' => 'sm'])
                                    <div>
                                        <a class="text-cream text-decoration-none" href="{{ route('admin.customers.show', $customer) }}">{{ $customer->name }}</a>
                                        <div class="text-muted-bp admin-meta">{{ $customer->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted-bp">{{ $customer->phone ?: '—' }}</td>
                            <td><span class="badge text-bg-secondary">{{ $customer->orders_count }}</span></td>
                            <td class="text-muted-bp">{{ $customer->created_at->format('M d, Y') }}</td>
                            <td>
                                @if($customer->is_active)
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-danger">Deactivated</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a class="btn btn-soft btn-sm" href="{{ route('admin.customers.show', $customer) }}">View</a>
                                    <form action="{{ route('admin.customers.toggle', $customer) }}" method="POST" data-confirm="{{ $customer->is_active ? 'Deactivate this customer account?' : 'Reactivate this customer account?' }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-link btn-sm {{ $customer->is_active ? 'text-danger' : 'text-success' }} p-1" type="submit" aria-label="{{ $customer->is_active ? 'Deactivate' : 'Reactivate' }} {{ $customer->name }}" data-bs-toggle="tooltip" title="{{ $customer->is_active ? 'Deactivate' : 'Reactivate' }}">
                                            <i class="bi {{ $customer->is_active ? 'bi-person-slash' : 'bi-person-check' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $customers->links() }}</div>
    @endif
</div>
@endsection

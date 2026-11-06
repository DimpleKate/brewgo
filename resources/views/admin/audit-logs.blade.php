@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Audit Logs</h1>
        <p class="admin-page-subtitle text-muted-bp">Track administrator and customer actions across the Brew &amp; Go console.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a class="btn btn-soft btn-sm" href="{{ route('admin.audit.export', array_filter(['search' => $search, 'method' => $method, 'role' => $role])) }}"><i class="bi bi-download me-1"></i>Export CSV</a>
        <div class="d-flex align-items-center gap-2 small text-muted-bp" aria-live="polite">
            <span class="live-dot"></span>
            <span data-audit-live-label>{{ $logs->onFirstPage() ? 'Live · listening' : 'Live updates start on page 1' }}</span>
        </div>
    </div>
</div>

<div class="admin-card mb-3">
    <form method="GET" action="{{ route('admin.audit.index') }}" class="row g-2 align-items-end" data-live-filter-form>
        <div class="col-md-6">
            <label class="form-label" for="auditSearch">Search</label>
            <div class="search-bp">
                <i class="bi bi-search"></i>
                <input id="auditSearch" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Search admin, action, route, or path">
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="auditMethod">Request method</label>
            <select id="auditMethod" name="method" class="form-select">
                <option value="">All methods</option>
                @foreach($methods as $requestMethod)
                    <option value="{{ $requestMethod }}" @selected($method === $requestMethod)>{{ $requestMethod }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="auditRole">Account type</label>
            <select id="auditRole" name="role" class="form-select">
                <option value="">All accounts</option>
                <option value="admin" @selected($role === 'admin')>Admins</option>
                <option value="customer" @selected($role === 'customer')>Customers</option>
            </select>
        </div>
        @if($search !== '' || $method !== '' || $role !== '')
            <div class="col-auto">
                <a class="btn btn-soft btn-sm" href="{{ route('admin.audit.index') }}">Reset</a>
            </div>
        @endif
    </form>
</div>

<div class="admin-card" data-audit-feed="{{ route('admin.audit.feed') }}" data-audit-live="{{ $logs->onFirstPage() ? '1' : '0' }}">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Request</th>
                    <th>IP address</th>
                    <th>Result</th>
                </tr>
            </thead>
            <tbody data-audit-tbody data-audit-last-id="{{ $latestId }}">
                @forelse($logs as $log)
                    <tr data-audit-id="{{ $log->id }}">
                        <td>
                            <div class="text-cream">{{ $log->createdAtInManila()->format('M d, Y · h:i:s A') }}</div>
                            <div class="text-muted-bp admin-meta">{{ $log->createdAtInManila()->diffForHumans() }} · PHT</div>
                        </td>
                        <td>
                            <div class="text-cream">{{ $log->user_name }}</div>
                            <span class="badge {{ ($log->actor_role ?: 'admin') === 'admin' ? 'text-bg-warning' : 'text-bg-info' }}">{{ ($log->actor_role ?: 'admin') === 'admin' ? 'Admin' : 'Customer' }}</span>
                        </td>
                        <td>
                            <div class="text-cream">{{ $log->action }}</div>
                            <div class="text-muted-bp admin-meta">{{ $log->route_name ?: '—' }}</div>
                        </td>
                        <td>
                            <span class="badge text-bg-secondary me-2">{{ $log->method }}</span>
                            <code class="small">{{ $log->path }}</code>
                        </td>
                        <td class="text-muted-bp">{{ $log->ip_address ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $log->status_code >= 400 ? 'text-bg-danger' : ($log->status_code >= 300 ? 'text-bg-warning' : 'text-bg-success') }}">{{ $log->status_code ?: '—' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr data-audit-empty>
                        <td colspan="6">
                            <div class="empty-state mb-0">
                                <i class="bi bi-journal-check"></i>
                                <p class="mb-0">No activity has been recorded yet.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="mt-3">{{ $logs->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/audit-live.js') }}"></script>
@endpush

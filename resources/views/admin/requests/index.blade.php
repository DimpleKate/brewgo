@extends('layouts.admin')

@section('title', 'Requests')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Cancellation &amp; refund requests</h1>
        <p class="admin-page-subtitle text-muted-bp">Review customer requests and record a decision.</p>
    </div>
    <form method="GET" action="{{ route('admin.requests.index') }}" class="admin-head-actions align-items-end">
        <div>
            <label class="form-label" for="status">Status</label>
            <select id="status" name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
            </select>
        </div>
        @if(request()->filled('status'))
            <a class="btn btn-soft btn-sm" href="{{ route('admin.requests.index') }}">Reset</a>
        @endif
    </form>
</div>

@if($requests->isEmpty())
    <div class="admin-card">
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p class="mb-0">No requests match this view.</p>
        </div>
    </div>
@else
    <div class="d-grid gap-3">
        @foreach($requests as $orderRequest)
            <div class="admin-card">
                <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge {{ $orderRequest->statusBadgeClass() }}">{{ ucfirst($orderRequest->status) }}</span>
                            <span class="text-cream fw-semibold">{{ $orderRequest->typeLabel() }}</span>
                            <span class="text-muted-bp small">·</span>
                            <a class="order-number small" href="{{ route('admin.orders.show', $orderRequest->order) }}">{{ $orderRequest->order?->order_number }}</a>
                        </div>

                        <div class="small text-muted-bp mt-2">{{ $orderRequest->created_at->format('M d, Y · h:i A') }} by {{ $orderRequest->user?->name }} ({{ $orderRequest->user?->email }})</div>
                        <p class="mt-2 mb-2">{{ $orderRequest->reason }}</p>

                        @if($orderRequest->admin_note)
                            <div class="promo-hint">
                                <span class="text-gold fw-semibold">Admin note:</span> {{ $orderRequest->admin_note }}
                            </div>
                        @endif

                        @if($orderRequest->reviewed_at)
                            <div class="small text-muted-bp mt-2">
                                Reviewed {{ $orderRequest->reviewed_at->format('M d, Y · h:i A') }}
                                @if($orderRequest->reviewer) by {{ $orderRequest->reviewer->name }} @endif
                            </div>
                        @endif
                    </div>

                    <div class="flex-shrink-0 request-decision">
                        @if($orderRequest->status === \App\Models\OrderRequest::STATUS_PENDING)
                            <form action="{{ route('admin.requests.review', $orderRequest) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <label class="form-label" for="decision-{{ $orderRequest->id }}">Decision</label>
                                <select id="decision-{{ $orderRequest->id }}" name="decision" class="form-select form-select-sm" required>
                                    <option value="approved">Approve {{ strtolower($orderRequest->typeLabel()) }}</option>
                                    <option value="rejected">Reject {{ strtolower($orderRequest->typeLabel()) }}</option>
                                </select>

                                <label class="form-label mt-2" for="admin-note-{{ $orderRequest->id }}">Admin note</label>
                                <textarea id="admin-note-{{ $orderRequest->id }}" name="admin_note" class="form-control form-control-sm" rows="2" maxlength="1000" placeholder="Required when rejecting."></textarea>

                                <button class="btn btn-gold btn-sm w-100 mt-2" type="submit">Submit review</button>
                            </form>
                        @else
                            <div class="text-lg-end">
                                <div class="small text-muted-bp">Order status</div>
                                <span class="status-badge {{ $orderRequest->order?->statusBadgeClass() }}">{{ $orderRequest->order?->statusLabel() }}</span>
                                <a class="btn btn-soft btn-sm w-100 mt-2" href="{{ route('admin.orders.show', $orderRequest->order) }}">View order</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-3">{{ $requests->links() }}</div>
@endif
@endsection

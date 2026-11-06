@extends('layouts.admin')

@section('title', 'Reviews')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Customer reviews</h1>
        <p class="admin-page-subtitle text-muted-bp">Approve, hide, or return customer feedback before it appears on product pages.</p>
    </div>
    <form method="GET" action="{{ route('admin.reviews.index') }}" class="admin-head-actions align-items-end" data-live-filter-form>
        <div>
            <label class="form-label" for="reviewSearch">Search</label>
            <div class="search-bp">
                <i class="bi bi-search"></i>
                <input id="reviewSearch" type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Customer, order, or feedback">
            </div>
        </div>
        <div>
            <label class="form-label" for="reviewStatus">Status</label>
            <select id="reviewStatus" name="status" class="form-select form-select-sm">
                <option value="">All ({{ $counts['all'] }})</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending ({{ $counts['pending'] }})</option>
                <option value="approved" @selected(request('status') === 'approved')>Approved ({{ $counts['approved'] }})</option>
                <option value="hidden" @selected(request('status') === 'hidden')>Hidden ({{ $counts['hidden'] }})</option>
            </select>
        </div>
        <div>
            <label class="form-label" for="reviewRating">Rating</label>
            <select id="reviewRating" name="rating" class="form-select form-select-sm">
                <option value="">Any rating</option>
                @for($star = 5; $star >= 1; $star--)
                    <option value="{{ $star }}" @selected((string) request('rating') === (string) $star)>{{ $star }} star{{ $star === 1 ? '' : 's' }}</option>
                @endfor
            </select>
        </div>
        @if(request()->hasAny(['search', 'status', 'rating']))
            <a class="btn btn-soft btn-sm" href="{{ route('admin.reviews.index') }}">Reset</a>
        @endif
    </form>
</div>

@if($reviews->isEmpty())
    <div class="admin-card">
        <div class="empty-state">
            <i class="bi bi-star"></i>
            <p class="mb-0">No reviews match this view.</p>
        </div>
    </div>
@else
    <div class="d-grid gap-3">
        @foreach($reviews as $review)
            <div class="admin-card">
                <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge {{ $review->statusBadgeClass() }}">{{ $review->statusLabel() }}</span>
                            @include('partials.star-rating', ['rating' => $review->rating])
                            <span class="text-cream fw-semibold">{{ $review->reviewerDisplayName() }}</span>
                            <span class="text-muted-bp small">·</span>
                            <a class="order-number small" href="{{ route('admin.orders.show', $review->order) }}">{{ $review->order?->order_number }}</a>
                        </div>

                        <div class="small text-muted-bp mt-2">
                            {{ $review->user?->email ?? 'Deleted account' }} · {{ $review->created_at->format('M d, Y · h:i A') }}
                        </div>

                        @if($review->comment)
                            <p class="mt-2 mb-2">{{ $review->comment }}</p>
                        @else
                            <p class="mt-2 mb-2 small text-muted-bp">No written feedback.</p>
                        @endif

                        @if($review->order?->items->isNotEmpty())
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                @foreach($review->order->items as $item)
                                    <span class="badge text-bg-dark border border-secondary-subtle">{{ $item->product_name }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if($review->moderated_at)
                            <div class="small text-muted-bp mt-2">
                                Moderated {{ $review->moderated_at->format('M d, Y · h:i A') }}
                                @if($review->moderator) by {{ $review->moderator->name }} @endif
                            </div>
                        @endif
                    </div>

                    <div class="flex-shrink-0 request-decision">
                        <form action="{{ route('admin.reviews.update', $review) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <label class="form-label" for="reviewStatus-{{ $review->id }}">Moderation</label>
                            <select id="reviewStatus-{{ $review->id }}" name="status" class="form-select form-select-sm" required>
                                <option value="pending" @selected($review->status === \App\Models\Review::STATUS_PENDING)>Pending review</option>
                                <option value="approved" @selected($review->status === \App\Models\Review::STATUS_APPROVED)>Approve &amp; publish</option>
                                <option value="hidden" @selected($review->status === \App\Models\Review::STATUS_HIDDEN)>Hide from storefront</option>
                            </select>
                            <button class="btn btn-gold btn-sm w-100 mt-2" type="submit">Save decision</button>
                            <a class="btn btn-soft btn-sm w-100 mt-2" href="{{ route('admin.orders.show', $review->order) }}">View order</a>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-3">{{ $reviews->links() }}</div>
@endif
@endsection

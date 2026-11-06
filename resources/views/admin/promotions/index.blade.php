@extends('layouts.admin')

@section('title', 'Promotions')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Promotions</h1>
        <p class="admin-page-subtitle text-muted-bp">Time-boxed discounts and promo codes, validated server-side at checkout.</p>
    </div>
    <a class="btn btn-gold btn-sm" href="{{ route('admin.promotions.create') }}"><i class="bi bi-plus-lg me-1"></i>New promotion</a>
</div>

<div class="admin-card">
    @if($promotions->isEmpty())
        <div class="empty-state">
            <i class="bi bi-ticket-perforated"></i>
            <p class="mb-3">No promotions yet.</p>
            <a class="btn btn-gold btn-sm" href="{{ route('admin.promotions.create') }}">Create one</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr>
                        <th>Promotion</th>
                        <th>Code</th>
                        <th>Value</th>
                        <th>Limits</th>
                        <th>Window</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($promotions as $promotion)
                        @php
                            $isRunning = $promotion->is_active
                                && (! $promotion->starts_at || $promotion->starts_at->isPast())
                                && (! $promotion->ends_at || $promotion->ends_at->isFuture());
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($promotion->image_url)
                                        <img class="admin-thumb" src="{{ $promotion->image_url }}" alt="{{ $promotion->title }}">
                                    @else
                                        <span class="admin-thumb admin-thumb-fallback media-fallback"><i class="bi bi-ticket-perforated"></i></span>
                                    @endif
                                    <div>
                                        <div class="text-cream">{{ $promotion->title }}</div>
                                        <div class="text-muted-bp admin-meta">Min. ₱{{ number_format((float) $promotion->minimum_spend, 2) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge text-bg-dark border border-warning">{{ $promotion->code ?: 'No code' }}</span></td>
                            <td class="text-gold">
                                {{ $promotion->discount_type === 'percent' ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2), '0'), '.').'%' : '₱'.number_format((float) $promotion->discount_value, 2) }}
                                @if($promotion->max_discount !== null)
                                    <div class="text-muted-bp admin-meta">max ₱{{ number_format((float) $promotion->max_discount, 2) }}</div>
                                @endif
                            </td>
                            <td class="text-muted-bp admin-meta">
                                {{ $promotion->scopeLabel() }}
                                <br>{{ $promotion->usageLabel() }}
                                @if($promotion->per_customer_limit !== null)
                                    <br>{{ $promotion->per_customer_limit }} per customer
                                @endif
                            </td>
                            <td class="text-muted-bp admin-meta">
                                @if($promotion->starts_at || $promotion->ends_at)
                                    {{ $promotion->starts_at?->format('M d, Y') ?? 'Whenever' }}
                                    <br>to {{ $promotion->ends_at?->format('M d, Y') ?? 'No end' }}
                                @else
                                    Always available
                                @endif
                            </td>
                            <td>
                                @if($isRunning)
                                    <span class="badge text-bg-success">Running</span>
                                @elseif($promotion->is_active)
                                    <span class="badge text-bg-warning">Scheduled</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a class="btn btn-soft btn-sm" href="{{ route('admin.promotions.edit', $promotion) }}">Edit</a>
                                    <form action="{{ route('admin.promotions.destroy', $promotion) }}" method="POST" data-confirm="Delete this promotion? Existing orders keep their saved code and discount.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link btn-sm text-danger p-1" type="submit" aria-label="Delete {{ $promotion->title }}"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $promotions->links() }}</div>
    @endif
</div>
@endsection

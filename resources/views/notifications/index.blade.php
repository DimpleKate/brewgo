@extends('layouts.app')

@section('title', 'Notifications | '.$site['brand_name'])

@section('content')
<section class="page-hero">
    <div class="container">
        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3">
            <div>
                <span class="eyebrow">Order updates</span>
                <h1>Your <span class="gold-text">notifications.</span></h1>
                <p class="section-subtitle mb-0">Pickup reminders, order updates, and messages from our team.</p>
            </div>
            @if($notifications->contains(fn ($notification) => $notification->read_at === null))
                <form action="{{ route('notifications.read-all') }}" method="POST" data-submit-once>
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-soft btn-sm" type="submit"><i class="bi bi-check2-all me-1"></i>Mark all as read</button>
                </form>
            @endif
        </div>
    </div>
</section>

<section class="section-pad pt-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @if($notifications->isEmpty())
                    <div class="empty-state">
                        <i class="bi bi-bell-slash"></i>
                        <h2 class="section-title fs-3">No notifications yet.</h2>
                        <p class="mb-3">We will let you know when an order is ready or needs your attention.</p>
                        <a class="btn btn-gold" href="{{ route('orders.index') }}">View your orders</a>
                    </div>
                @else
                    <div class="panel panel-pad">
                        @foreach($notifications as $notification)
                            @php
                                $data = $notification->data;
                                $status = \App\Enums\OrderStatus::tryFrom($data['status'] ?? '');
                                $pickupAt = isset($data['pickup_at']) ? \Illuminate\Support\Carbon::parse($data['pickup_at']) : null;
                                $total = isset($data['order_total']) ? (float) $data['order_total'] : null;
                            @endphp
                            <a class="d-block text-decoration-none border-bottom border-secondary-subtle py-3 {{ $notification->read_at === null ? 'opacity-100' : 'opacity-75' }}"
                               href="{{ route('notifications.open', $notification->id) }}">
                                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="text-cream fw-semibold">{{ $data['title'] ?? 'Order update' }}</span>
                                            @if($notification->read_at === null)
                                                <span class="badge text-bg-warning">New</span>
                                            @endif
                                            @if($status)
                                                <span class="badge {{ $status->badgeClass() }}">{{ $status->label() }}</span>
                                            @endif
                                        </div>
                                        <div class="small text-muted-bp mt-1">{{ $data['message'] ?? '' }}</div>

                                        @if(isset($data['order_number']) || $pickupAt || $total !== null)
                                            <div class="d-flex flex-wrap align-items-center gap-3 small text-muted-bp mt-2">
                                                @if(! empty($data['order_number']))
                                                    <span><i class="bi bi-receipt me-1"></i>{{ $data['order_number'] }}</span>
                                                @endif
                                                @if($pickupAt)
                                                    <span><i class="bi bi-clock me-1"></i>Pickup {{ $pickupAt->format('M d, Y · h:i A') }}</span>
                                                @endif
                                                @if($total !== null)
                                                    <span><i class="bi bi-wallet2 me-1"></i>₱{{ number_format($total, 2) }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    <div class="small text-muted-bp text-nowrap">{{ $notification->created_at->diffForHumans() }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-4">{{ $notifications->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

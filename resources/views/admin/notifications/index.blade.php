@extends('layouts.admin')

@section('title', 'Notifications')

@section('content')
<div class="admin-page-head">
    <div>
        <div class="admin-page-eyebrow">Operations</div>
        <h1 class="admin-page-title">Notifications</h1>
        <p class="admin-page-subtitle text-muted-bp">Overdue pickups and order alerts for the team.</p>
    </div>
    <div class="admin-head-actions">
        @if($notifications->contains(fn ($notification) => $notification->read_at === null))
            <form action="{{ route('admin.notifications.read-all') }}" method="POST" data-submit-once>
                @csrf
                @method('PATCH')
                <button class="btn btn-soft btn-sm" type="submit"><i class="bi bi-check2-all me-1"></i>Mark all as read</button>
            </form>
        @endif
        <a class="btn btn-soft btn-sm" href="{{ route('admin.dashboard') }}"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
    </div>
</div>

<div class="admin-card">
    @if($notifications->isEmpty())
        <div class="empty-state">
            <i class="bi bi-bell-slash"></i>
            <p class="mb-0">No notifications yet.</p>
        </div>
    @else
        @foreach($notifications as $notification)
            <a class="d-block text-decoration-none border-bottom border-secondary-subtle py-2 {{ $notification->read_at === null ? '' : 'opacity-75' }}"
               href="{{ route('admin.notifications.open', $notification->id) }}">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                    <div>
                        <div class="text-cream fw-semibold">
                            @if($notification->read_at === null)
                                <span class="badge text-bg-warning me-1">New</span>
                            @endif
                            {{ $notification->data['title'] ?? 'Order alert' }}
                            @if(! empty($notification->data['order_number']))
                                <span class="text-gold">· {{ $notification->data['order_number'] }}</span>
                            @endif
                        </div>
                        <div class="small text-muted-bp mt-1">{{ $notification->data['message'] ?? '' }}</div>
                    </div>
                    <div class="small text-muted-bp">{{ $notification->created_at->diffForHumans() }}</div>
                </div>
            </a>
        @endforeach

        <div class="mt-3">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection

@php
    $pendingOrderCount = \App\Models\Order::whereIn('status', [
        \App\Enums\OrderStatus::PendingPayment,
        \App\Enums\OrderStatus::Pending,
    ])->count();
    $overdueOrderCount = \App\Models\Order::where('status', \App\Enums\OrderStatus::Overdue)->count();
    $pendingRequestCount = \App\Models\OrderRequest::where('status', \App\Models\OrderRequest::STATUS_PENDING)->count();
    $pendingReviewCount = \App\Models\Review::where('status', \App\Models\Review::STATUS_PENDING)->count();
    $notificationCount = auth()->user()?->unreadNotifications()->count() ?? 0;
@endphp

<nav class="admin-nav">
    <div class="admin-nav-label">Overview</div>
    <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
        <i class="bi bi-speedometer2"></i> <span class="admin-nav-text">Dashboard</span>
    </a>
    <a class="{{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}" href="{{ route('admin.notifications.index') }}">
        <i class="bi bi-bell"></i> <span class="admin-nav-text">Notifications</span>
        @if($notificationCount > 0)
            <span class="nav-badge">{{ $notificationCount > 99 ? '99+' : $notificationCount }}</span>
        @endif
    </a>

    <div class="admin-nav-label">Catalog</div>
    <a class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
        <i class="bi bi-cup-hot"></i> <span class="admin-nav-text">Products</span>
    </a>
    <a class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
        <i class="bi bi-tags"></i> <span class="admin-nav-text">Categories</span>
    </a>

    <div class="admin-nav-label">Sales</div>
    <a class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
        <i class="bi bi-receipt"></i> <span class="admin-nav-text">Orders</span>
        @if($pendingOrderCount > 0)
            <span class="nav-badge">{{ $pendingOrderCount > 99 ? '99+' : $pendingOrderCount }}</span>
        @endif
    </a>
    @if($overdueOrderCount > 0)
        <a class="{{ request()->routeIs('admin.orders.index') && request('status') === 'Overdue' ? 'active' : '' }}" href="{{ route('admin.orders.index', ['status' => 'Overdue']) }}">
            <i class="bi bi-alarm text-danger"></i> <span class="admin-nav-text">Overdue pickups</span>
            <span class="nav-badge">{{ $overdueOrderCount > 99 ? '99+' : $overdueOrderCount }}</span>
        </a>
    @endif
    <a class="{{ request()->routeIs('admin.requests.*') ? 'active' : '' }}" href="{{ route('admin.requests.index') }}">
        <i class="bi bi-inbox"></i> <span class="admin-nav-text">Requests</span>
        @if($pendingRequestCount > 0)
            <span class="nav-badge">{{ $pendingRequestCount > 99 ? '99+' : $pendingRequestCount }}</span>
        @endif
    </a>
    <a class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
        <i class="bi bi-graph-up-arrow"></i> <span class="admin-nav-text">Sales reports</span>
    </a>
    <a class="{{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}" href="{{ route('admin.refunds.index') }}">
        <i class="bi bi-arrow-counterclockwise"></i> <span class="admin-nav-text">Refunds</span>
    </a>

    <div class="admin-nav-label">People</div>
    <a class="{{ request()->routeIs('admin.customers.*') ? 'active' : '' }}" href="{{ route('admin.customers.index') }}">
        <i class="bi bi-people"></i> <span class="admin-nav-text">Customers</span>
    </a>
    <a class="{{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}" href="{{ route('admin.reviews.index') }}">
        <i class="bi bi-star-half"></i> <span class="admin-nav-text">Reviews</span>
        @if($pendingReviewCount > 0)
            <span class="nav-badge">{{ $pendingReviewCount > 99 ? '99+' : $pendingReviewCount }}</span>
        @endif
    </a>

    <div class="admin-nav-label">Content</div>
    <a class="{{ request()->routeIs('admin.addons.*') ? 'active' : '' }}" href="{{ route('admin.addons.index') }}">
        <i class="bi bi-plus-square"></i> <span class="admin-nav-text">Add-ons</span>
    </a>
    <a class="{{ request()->routeIs('admin.promotions.*') ? 'active' : '' }}" href="{{ route('admin.promotions.index') }}">
        <i class="bi bi-ticket-perforated"></i> <span class="admin-nav-text">Promotions</span>
    </a>
    <a class="{{ request()->routeIs('admin.carousel.*') ? 'active' : '' }}" href="{{ route('admin.carousel.index') }}">
        <i class="bi bi-images"></i> <span class="admin-nav-text">Carousel</span>
    </a>

    <div class="admin-nav-label">System</div>
    <a class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}">
        <i class="bi bi-gear"></i> <span class="admin-nav-text">Settings</span>
    </a>
    <a class="{{ request()->routeIs('admin.security.*') ? 'active' : '' }}" href="{{ route('admin.security.edit') }}">
        <i class="bi bi-shield-lock"></i> <span class="admin-nav-text">My security</span>
    </a>
    @if(auth()->user()?->hasRole(['super_admin']))
        <a class="{{ request()->routeIs('admin.staff.*') ? 'active' : '' }}" href="{{ route('admin.staff.index') }}">
            <i class="bi bi-person-badge"></i> <span class="admin-nav-text">Administrators</span>
        </a>
    @endif
    <a class="{{ request()->routeIs('admin.health.*') ? 'active' : '' }}" href="{{ route('admin.health.index') }}">
        <i class="bi bi-heart-pulse"></i> <span class="admin-nav-text">System health</span>
    </a>
    <a class="{{ request()->routeIs('admin.audit.*') ? 'active' : '' }}" href="{{ route('admin.audit.index') }}">
        <i class="bi bi-journal-text"></i> <span class="admin-nav-text">Audit logs</span>
    </a>
</nav>

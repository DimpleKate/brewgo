<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $excludedStatuses = [OrderStatus::Cancelled->value, OrderStatus::Rejected->value];

        $validOrders = Order::query()->whereNotIn('status', $excludedStatuses);
        $todayOrders = (clone $validOrders)->whereDate('created_at', today());
        $weekStart = now()->subDays(6)->startOfDay();
        $weekOrders = (clone $validOrders)
            ->where('created_at', '>=', $weekStart)
            ->get(['created_at', 'total']);

        $dailySales = collect(range(6, 0))
            ->map(function (int $daysAgo) use ($weekOrders): array {
                $date = now()->subDays($daysAgo)->startOfDay();
                $orders = $weekOrders->filter(fn (Order $order) => $order->created_at->isSameDay($date));

                return [
                    'label' => $date->format('D'),
                    'date' => $date->format('M d'),
                    'orders' => $orders->count(),
                    'revenue' => (float) $orders->sum('total'),
                ];
            })
            ->values();

        $stats = [
            'today_orders' => (clone $todayOrders)->count(),
            'today_revenue' => (float) (clone $todayOrders)->sum('total'),
            'week_orders' => $weekOrders->count(),
            'week_revenue' => (float) $weekOrders->sum('total'),
            'month_revenue' => (float) (clone $validOrders)
                ->whereBetween('created_at', [now()->startOfMonth(), now()])
                ->sum('total'),
            'average_order_value' => (float) (clone $validOrders)->avg('total'),
            'pending_orders' => Order::whereIn('status', [
                OrderStatus::PendingPayment,
                OrderStatus::Pending,
            ])->count(),
            'active_orders' => Order::whereIn('status', [
                OrderStatus::Paid,
                OrderStatus::Confirmed,
                OrderStatus::Preparing,
                OrderStatus::Ready,
            ])->count(),
            'overdue_orders' => Order::where('status', OrderStatus::Overdue)->count(),
            'customers' => User::where('is_admin', false)->count(),
            'pending_requests' => OrderRequest::where('status', OrderRequest::STATUS_PENDING)->count(),
            'products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
        ];

        $recentOrders = Order::with('user')->latest()->take(8)->get();

        $statusCounts = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingRequests = OrderRequest::with(['order', 'user'])
            ->where('status', OrderRequest::STATUS_PENDING)
            ->latest()
            ->take(5)
            ->get();

        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', $excludedStatuses)
            ->selectRaw('order_items.product_name, SUM(order_items.quantity) as units, SUM(order_items.line_total) as revenue')
            ->groupBy('order_items.product_name')
            ->orderByDesc('units')
            ->limit(5)
            ->get();

        $recentCustomers = User::query()
            ->where('is_admin', false)
            ->withCount('orders')
            ->latest()
            ->take(5)
            ->get();

        $maxDailyRevenue = max(1.0, (float) $dailySales->max('revenue'));

        return view('admin.dashboard', compact(
            'stats',
            'recentOrders',
            'statusCounts',
            'pendingRequests',
            'dailySales',
            'maxDailyRevenue',
            'topProducts',
            'recentCustomers',
        ));
    }
}

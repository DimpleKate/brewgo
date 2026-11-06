<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): View
    {
        $query = Order::with('user')->withCount('items')->latest();

        if ($request->filled('status') && in_array($request->status, OrderStatus::values(), true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status')->toString());
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('pickup_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load([
            'items',
            'user',
            'statusHistories.changedBy',
            'requests.reviewer',
            'promotion',
            'payments.verifier',
            'payments.rejector',
            'receipts',
            'wasteItems.logger',
            'refunds.processor',
        ]);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(OrderStatusRequest $request, Order $order): RedirectResponse
    {
        $status = OrderStatus::from($request->string('status')->toString());

        $this->orderService->updateStatus($order, $status, $request->input('note'), $request->user());

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order status updated.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\StoreOrderRequestRequest;
use App\Models\Order;
use App\Models\Review;
use App\Services\OrderRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderRequestService $requestService) {}

    public function index(Request $request): View
    {
        $query = $request->user()->orders()->with(['items'])->latest();

        if ($request->filled('status') && in_array($request->status, OrderStatus::values(), true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%'.trim($request->string('search')->toString()).'%');
        }

        $orders = $query->paginate(10)->withQueryString();

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load([
            'items',
            'statusHistories.changedBy',
            'requests.reviewer',
            'promotion',
            'user',
            'payments.verifier',
            'payments.rejector',
            'receipts',
            'review.moderator',
        ]);

        return view('orders.show', compact('order'));
    }

    public function storeRequest(StoreOrderRequestRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('requestService', $order);

        $this->requestService->create(
            $order,
            $request->user(),
            $request->string('type')->toString(),
            $request->string('reason')->toString()
        );

        return redirect()->route('orders.show', $order)->with('success', 'Your request has been submitted for admin review.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('requestService', $order);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->requestService->cancelImmediately($order, $request->user(), $validated['reason']);

        return redirect()->route('orders.show', $order)->with('success', 'Your order has been cancelled.');
    }

    public function storeReview(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('review', $order);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $comment = trim((string) ($validated['comment'] ?? ''));

        $review = $order->review()->firstOrCreate([], [
            'user_id' => $request->user()->id,
            'rating' => (int) $validated['rating'],
            'comment' => $comment !== '' ? $comment : null,
            'status' => Review::STATUS_PENDING,
        ]);

        if (! $review->wasRecentlyCreated) {
            return redirect()
                ->route('orders.show', $order)
                ->with('error', 'You have already reviewed this order.');
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Thank you for your feedback! Your review is awaiting moderation.');
    }
}

<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderRequest;
use App\Models\User;
use App\Notifications\AdminAlert;
use App\Notifications\OrderRequestUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderRequestService
{
    public function __construct(
        private readonly WasteService $waste,
        private readonly OrderStatusNotifier $statusNotifier,
        private readonly RefundService $refunds,
    ) {}

    public function create(Order $order, User $user, string $type, string $reason): OrderRequest
    {
        $request = DB::transaction(function () use ($order, $user, $type, $reason) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->user_id !== $user->id) {
                abort(403);
            }

            if ($type === OrderRequest::TYPE_CANCELLATION && ! $order->canBeCancelledByCustomer()) {
                throw ValidationException::withMessages([
                    'type' => 'This order can no longer be cancelled. You may request a refund after completion.',
                ]);
            }

            if ($type === OrderRequest::TYPE_REFUND && ! $order->canBeRefundedByCustomer()) {
                throw ValidationException::withMessages([
                    'type' => 'A refund can only be requested for a paid order that is ready, completed, cancelled, rejected, or overdue.',
                ]);
            }

            if ($order->requests()->where('status', OrderRequest::STATUS_PENDING)->exists()) {
                throw ValidationException::withMessages([
                    'type' => 'You already have a pending request for this order.',
                ]);
            }

            return OrderRequest::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'type' => $type,
                'reason' => $reason,
                'status' => OrderRequest::STATUS_PENDING,
            ]);
        }, 3);

        User::query()
            ->where('is_admin', true)
            ->where('is_active', true)
            ->get()
            ->each(function (User $admin) use ($request) {
                try {
                    $admin->notify(new AdminAlert(
                        title: 'New '.$request->typeLabel().' request',
                        message: $request->user->name.' submitted a '.$request->typeLabel().' request for order '.$request->order->order_number.'.',
                        url: route('admin.requests.index'),
                        orderNumber: $request->order->order_number,
                    ));
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

        return $request;
    }

    public function cancelImmediately(Order $order, User $user, string $reason): Order
    {
        $previousStatus = $order->status;

        $order = DB::transaction(function () use ($order, $user, $reason) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->user_id !== $user->id) {
                abort(403);
            }

            if (! $order->canBeCancelledImmediately()) {
                throw ValidationException::withMessages([
                    'order' => 'Preparation has started, so this order can no longer be cancelled online. Please submit a support request.',
                ]);
            }

            $this->waste->recordForCancellation($order, $user, $reason);

            $order->restockItemsIfUnprepared();

            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_reason' => $reason,
                'cancelled_by' => $user->id,
            ]);

            $order->promotion?->releaseRedemption($order);

            $order->statusHistories()->create([
                'status' => OrderStatus::Cancelled->value,
                'note' => 'Order cancelled by the customer before preparation. '.$reason,
                'changed_by' => $user->id,
            ]);

            return $order->refresh();
        }, 3);

        $this->statusNotifier->notify($order, OrderStatus::Cancelled, $previousStatus);

        return $order;
    }

    public function review(OrderRequest $request, User $admin, string $decision, ?string $note): OrderRequest
    {
        $cancelledOrder = null;
        $previousStatus = null;

        $request = DB::transaction(function () use ($request, $admin, $decision, $note, &$cancelledOrder, &$previousStatus) {
            $request = OrderRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($request->order_id)->lockForUpdate()->firstOrFail();

            if ($request->status !== OrderRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'decision' => 'This request has already been reviewed.',
                ]);
            }

            if ($decision === OrderRequest::STATUS_APPROVED) {
                if ($request->type === OrderRequest::TYPE_CANCELLATION && ! $order->status->isFinal()) {
                    $reason = trim((string) ($note ?: $request->reason));

                    $previousStatus = $order->status;

                    $this->waste->recordForCancellation($order, $admin, $reason);

                    $order->restockItemsIfUnprepared();

                    $order->update([
                        'status' => OrderStatus::Cancelled,
                        'cancelled_at' => now(),
                        'cancelled_reason' => $reason,
                        'cancelled_by' => $admin->id,
                    ]);

                    $order->promotion?->releaseRedemption($order);

                    $order->statusHistories()->create([
                        'status' => OrderStatus::Cancelled->value,
                        'note' => 'Cancellation request approved by admin. '.$reason,
                        'changed_by' => $admin->id,
                    ]);

                    $cancelledOrder = $order->refresh();
                }

                if ($request->type === OrderRequest::TYPE_REFUND && $this->refunds->refundableTotal($order) > 0) {
                    $this->refunds->record($order, $admin, [
                        'reason' => $request->reason,
                        'notes' => $note,
                    ], $request);
                }
            }

            $request->update([
                'status' => $decision,
                'admin_note' => $note,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            return $request->refresh();
        }, 3);

        $request->loadMissing(['order', 'user']);

        try {
            $request->user?->notify(new OrderRequestUpdated($request));
        } catch (Throwable $exception) {
            report($exception);
        }

        if ($cancelledOrder && $previousStatus) {
            $this->statusNotifier->notify($cancelledOrder, OrderStatus::Cancelled, $previousStatus);
        }

        return $request;
    }
}

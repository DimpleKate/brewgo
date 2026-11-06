<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class WasteService
{
    public function recordForCancellation(Order $order, ?User $actor, ?string $reason = null): int
    {
        if ($order->wasteItems()->exists()) {
            return 0;
        }

        $wasPrepared = in_array($order->status, [
            OrderStatus::Preparing,
            OrderStatus::Ready,
            OrderStatus::Overdue,
        ], true);

        if (! $wasPrepared) {
            return 0;
        }

        $order->loadMissing('items');
        $wasteReason = $reason ?: 'Order cancelled after preparation started.';

        foreach ($order->items as $item) {
            $order->wasteItems()->create([
                'order_item_id' => $item->id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'reason' => $wasteReason,
                'logged_by' => $actor?->id,
            ]);
        }

        return (int) $order->items->sum('quantity');
    }
}

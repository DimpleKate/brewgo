<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderOverdue;
use App\Notifications\OrderReadyForPickup;
use App\Notifications\OrderStatusChanged;
use Illuminate\Notifications\Notification;
use Throwable;

class OrderStatusNotifier
{
    public function notify(Order $order, OrderStatus $status, ?OrderStatus $previousStatus = null): void
    {
        $order->loadMissing('user');

        if ($order->user) {
            $notification = $status === OrderStatus::Ready && $order->isPickup()
                ? new OrderReadyForPickup($order)
                : new OrderStatusChanged($order, $status, $previousStatus);

            $this->send($order->user, $notification);
        }

        if ($status === OrderStatus::Overdue) {
            User::query()
                ->where('is_admin', true)
                ->get()
                ->each(fn (User $admin) => $this->send($admin, new OrderOverdue($order)));
        }
    }

    private function send(User $user, Notification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}

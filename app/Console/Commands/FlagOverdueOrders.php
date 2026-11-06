<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:flag-overdue')]
#[Description('Flag ready pickup orders that passed their pickup deadline as overdue.')]
class FlagOverdueOrders extends Command
{
    public function handle(OrderService $orders): int
    {
        $candidates = Order::query()
            ->where('status', OrderStatus::Ready)
            ->whereNotNull('pickup_deadline_at')
            ->where('pickup_deadline_at', '<=', now())
            ->get();

        foreach ($candidates as $order) {
            $orders->updateStatus(
                $order,
                OrderStatus::Overdue,
                'Pickup deadline passed at '.$order->pickup_deadline_at?->format('M d, Y · h:i A').'. The order was not collected.',
            );
        }

        $this->info('Flagged '.$candidates->count().' overdue pickup order(s).');

        return self::SUCCESS;
    }
}

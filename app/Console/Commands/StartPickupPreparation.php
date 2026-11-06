<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\OrderSettingsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:start-preparation')]
#[Description('Start preparing paid pickup orders whose scheduled pickup time is close.')]
class StartPickupPreparation extends Command
{
    public function handle(OrderService $orders, OrderSettingsService $settings): int
    {
        $threshold = now()->addMinutes($settings->prepMinutes());

        $candidates = Order::query()
            ->where('status', OrderStatus::Paid)
            ->where('service_type', Order::SERVICE_PICKUP)
            ->whereNotNull('pickup_at')
            ->where('pickup_at', '<=', $threshold)
            ->get();

        $started = 0;

        foreach ($candidates as $order) {
            if (! $order->isClearedForPreparation()) {
                continue;
            }

            $orders->updateStatus(
                $order,
                OrderStatus::Preparing,
                'Preparation started automatically for the scheduled pickup time.',
            );

            $started++;
        }

        $this->info('Started preparation for '.$started.' pickup order(s).');

        return self::SUCCESS;
    }
}

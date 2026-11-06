<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\OrderSettingsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:expire-unpaid {--minutes= : Override the configured unpaid expiry window} {--dry-run : List matching orders without cancelling them}')]
#[Description('Cancel orders that were placed but never had their required payment verified.')]
class ExpireUnpaidOrders extends Command
{
    public function handle(OrderService $orders, OrderSettingsService $settings): int
    {
        $minutes = (int) ($this->option('minutes') ?: $settings->unpaidExpiryMinutes());
        $minutes = max(5, $minutes);

        $candidates = Order::query()
            ->where('status', OrderStatus::PendingPayment)
            ->whereNull('paid_at')
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->get();

        if ($this->option('dry-run')) {
            $this->info('Found '.$candidates->count().' unpaid order(s) older than '.$minutes.' minute(s).');

            return self::SUCCESS;
        }

        $expired = 0;

        foreach ($candidates as $order) {
            $orders->updateStatus(
                $order,
                OrderStatus::Cancelled,
                'Order automatically cancelled: the required payment was not verified within '.$minutes.' minutes.',
            );

            $expired++;
        }

        $this->info('Expired '.$expired.' unpaid order(s).');

        return self::SUCCESS;
    }
}

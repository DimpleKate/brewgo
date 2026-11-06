<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderRequest;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function refundedTotal(Order $order): float
    {
        return round((float) $order->refunds()
            ->whereIn('status', [Refund::STATUS_APPROVED, Refund::STATUS_PROCESSED])
            ->sum('amount'), 2);
    }

    public function refundableTotal(Order $order): float
    {
        return round(max(0, (float) $order->amount_paid - $this->refundedTotal($order)), 2);
    }

    /**
     * @param  array{amount?:float|int|string|null, method?:string|null, reference?:string|null, reason?:string|null, notes?:string|null}  $data
     */
    public function record(Order $order, User $admin, array $data = [], ?OrderRequest $request = null, ?Payment $payment = null): Refund
    {
        return DB::transaction(function () use ($order, $admin, $data, $request, $payment) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $refundable = $this->refundableTotal($order);

            if ($refundable <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'This order has no refundable amount remaining.',
                ]);
            }

            $amount = round((float) ($data['amount'] ?? $refundable), 2);

            if ($amount <= 0 || $amount > $refundable + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => 'The refund must be greater than zero and cannot exceed ₱'.number_format($refundable, 2).'.',
                ]);
            }

            $method = $data['method'] ?? $payment?->method ?? $order->payment_method ?? Payment::METHOD_CASH;

            $refund = Refund::create([
                'order_id' => $order->id,
                'order_request_id' => $request?->id,
                'payment_id' => $payment?->id ?? $this->latestVerifiedPaymentId($order),
                'amount' => $amount,
                'method' => $method,
                'reference' => $data['reference'] ?? null,
                'status' => Refund::STATUS_PROCESSED,
                'reason' => $data['reason'] ?? $request?->reason,
                'notes' => $data['notes'] ?? null,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            if ($this->refundableTotal($order) <= 0.001) {
                $order->update(['payment_status' => Order::PAYMENT_REFUNDED]);
            }

            $order->statusHistories()->create([
                'status' => $order->status->value,
                'note' => 'Refund of ₱'.number_format($amount, 2).' recorded via '.$refund->methodLabel().'.'.($refund->reference ? ' Reference: '.$refund->reference.'.' : ''),
                'changed_by' => $admin->id,
            ]);

            return $refund;
        }, 3);
    }

    private function latestVerifiedPaymentId(Order $order): ?int
    {
        return $order->payments()
            ->where('status', Payment::STATUS_VERIFIED)
            ->latest('id')
            ->value('id');
    }
}

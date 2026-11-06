<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use App\Notifications\AdminAlert;
use App\Notifications\PaymentRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentService
{
    public function __construct(private readonly OrderStatusNotifier $statusNotifier) {}

    public function verify(Order $order, Payment $payment, User $admin, ?string $note = null): Receipt
    {
        return DB::transaction(function () use ($order, $payment, $admin, $note) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->order_id !== $order->id) {
                abort(404);
            }

            if (! $payment->isPending()) {
                throw ValidationException::withMessages([
                    'payment' => 'Only pending payments can be verified.',
                ]);
            }

            $payment->update([
                'status' => Payment::STATUS_VERIFIED,
                'verified_by' => $admin->id,
                'verified_at' => now(),
                'notes' => $note ?: $payment->notes,
            ]);

            return $this->applyVerifiedPayment($order, $payment, $admin, $note);
        }, 3);
    }

    /**
     * @param  array{method:string, amount:float|int|string, reference?:string|null, notes?:string|null}  $data
     */
    public function record(Order $order, array $data, User $admin): Receipt
    {
        return DB::transaction(function () use ($order, $data, $admin) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $amount = round((float) $data['amount'], 2);
            $balance = $order->balanceDue();

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Enter a payment amount greater than zero.',
                ]);
            }

            if ($amount > $balance + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment cannot exceed the remaining balance of ₱'.number_format($balance, 2).'.',
                ]);
            }

            $payment = $order->payments()->create([
                'method' => $data['method'],
                'type' => $this->paymentType($order, $amount),
                'amount' => $amount,
                'status' => Payment::STATUS_VERIFIED,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            return $this->applyVerifiedPayment($order, $payment, $admin, $data['notes'] ?? null);
        }, 3);
    }

    public function reject(Order $order, Payment $payment, User $admin, ?string $reason = null): Payment
    {
        $payment = DB::transaction(function () use ($order, $payment, $admin, $reason) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->order_id !== $order->id) {
                abort(404);
            }

            if (! $payment->isPending()) {
                throw ValidationException::withMessages([
                    'payment' => 'Only pending payments can be rejected.',
                ]);
            }

            $payment->update([
                'status' => Payment::STATUS_REJECTED,
                'rejected_by' => $admin->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $hasPending = $order->payments()
                ->where('status', Payment::STATUS_PENDING)
                ->exists();

            if (! $hasPending && (float) $order->amount_paid <= 0) {
                $order->update(['payment_status' => Order::PAYMENT_UNPAID]);
            }

            $order->statusHistories()->create([
                'status' => $order->status->value,
                'note' => 'Payment of ₱'.number_format((float) $payment->amount, 2).' was rejected.'.($reason ? ' '.$reason : ''),
                'changed_by' => $admin->id,
            ]);

            return $payment->refresh();
        }, 3);

        try {
            $order->user?->notify(new PaymentRejected($order, $payment));
        } catch (Throwable $exception) {
            report($exception);
        }

        User::query()
            ->where('is_admin', true)
            ->where('is_active', true)
            ->get()
            ->each(function (User $adminUser) use ($order, $reason) {
                try {
                    $adminUser->notify(new AdminAlert(
                        title: 'Payment rejected',
                        message: 'A payment for order '.$order->order_number.' was rejected.'.($reason ? ' '.$reason : ''),
                        url: route('admin.orders.show', $order),
                        orderNumber: $order->order_number,
                    ));
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

        return $payment;
    }

    private function applyVerifiedPayment(Order $order, Payment $payment, User $admin, ?string $note): Receipt
    {
        $order->amount_paid = round((float) $order->amount_paid + (float) $payment->amount, 2);
        $order->payment_method = $payment->method;
        $order->payment_status = $order->amount_paid + 0.001 >= (float) $order->total
            ? Order::PAYMENT_PAID
            : Order::PAYMENT_DEPOSIT;
        $order->paid_at ??= now();

        $previousStatus = $order->status;

        if (in_array($order->status, [OrderStatus::PendingPayment, OrderStatus::Pending, OrderStatus::Confirmed], true)
            && $order->isClearedForPreparation()) {
            $order->status = OrderStatus::Paid;
        }

        $order->save();
        $order->refresh();

        $receipt = $this->createReceipt($order, $payment, $admin);

        if ($order->status !== $previousStatus) {
            $order->statusHistories()->create([
                'status' => $order->status->value,
                'note' => 'Payment verified by staff. Receipt '.$receipt->receipt_number.'.',
                'changed_by' => $admin->id,
            ]);

            $this->statusNotifier->notify($order, $order->status, $previousStatus);
        } else {
            $order->statusHistories()->create([
                'status' => $order->status->value,
                'note' => 'Additional payment verified. Receipt '.$receipt->receipt_number.'.'.($note ? ' '.$note : ''),
                'changed_by' => $admin->id,
            ]);
        }

        return $receipt;
    }

    private function paymentType(Order $order, float $amount): string
    {
        $balance = $order->balanceDue();

        if ($amount + 0.001 >= $balance) {
            return (float) $order->amount_paid > 0 ? Payment::TYPE_BALANCE : Payment::TYPE_FULL;
        }

        return Payment::TYPE_DEPOSIT;
    }

    private function createReceipt(Order $order, Payment $payment, User $admin): Receipt
    {
        $order->load(['items']);

        do {
            $receiptNumber = Receipt::generateReceiptNumber();
        } while (Receipt::where('receipt_number', $receiptNumber)->exists());

        $snapshot = [
            'order_number' => $order->order_number,
            'service_type' => $order->service_type,
            'pickup_at' => $order->pickup_at?->toDateTimeString(),
            'pickup_name' => $order->pickup_name,
            'items' => $order->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
                'options' => $item->options_snapshot ?? [],
                'addons' => $item->addons_snapshot ?? [],
                'note' => $item->note,
            ])->values()->all(),
            'subtotal' => (float) $order->subtotal,
            'discount' => (float) $order->discount,
            'total' => (float) $order->total,
            'amount_paid' => (float) $order->amount_paid,
            'balance_due' => $order->balanceDue(),
            'payment_method' => $payment->method,
            'payment_type' => $payment->type,
            'reference' => $payment->reference,
        ];

        return Receipt::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'receipt_number' => $receiptNumber,
            'amount' => $payment->amount,
            'balance_due' => $order->balanceDue(),
            'payment_method' => $payment->method,
            'snapshot' => $snapshot,
            'issued_by' => $admin->id,
            'issued_at' => now(),
        ]);
    }
}

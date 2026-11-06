<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use App\Notifications\AdminAlert;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly OrderSettingsService $settings,
        private readonly WasteService $waste,
        private readonly PaymentProofService $paymentProofs,
        private readonly OrderStatusNotifier $statusNotifier,
    ) {}

    /**
     * @param  array{service_type:string, people_count?:int|null, pickup_name?:string|null, pickup_phone?:string|null, pickup_at?:string|null, payment_method?:string|null, payment_reference?:string|null, payment_proof?:UploadedFile|null, notes?:string|null, promotion_code?:string|null}  $data
     */
    public function place(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $cart = Cart::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $cart) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $items = $cart->items()
                ->where('is_selected', true)
                ->with(['product.options', 'product.addons'])
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Select at least one item to checkout.']);
            }

            $lineItems = [];
            $productQuantities = [];
            $lockedProducts = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                $product = Product::whereKey($item->product_id)->lockForUpdate()->first();

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => 'A product in your cart is no longer available. Please remove it and try again.',
                    ]);
                }

                if (! $product->isOrderable()) {
                    throw ValidationException::withMessages([
                        'cart' => $product->name.' is currently sold out. Please remove it and try again.',
                    ]);
                }

                $quote = $this->pricing->quote(
                    $product,
                    $item->options ?? [],
                    $item->addons ?? [],
                    $item->quantity
                );

                $subtotal += $quote['line_total'];
                $productQuantities[$product->id] = ($productQuantities[$product->id] ?? 0) + $item->quantity;
                $lockedProducts[$product->id] = $product;

                $lineItems[] = [
                    'product' => $product,
                    'quote' => $quote,
                    'note' => $item->note,
                ];
            }

            foreach ($productQuantities as $productId => $quantity) {
                $product = $lockedProducts[$productId];

                if (! $product->hasStockFor($quantity)) {
                    $available = max(0, (int) $product->stock_quantity);

                    throw ValidationException::withMessages([
                        'cart' => $available > 0
                            ? 'Only '.$available.' '.$product->name.' '.($available === 1 ? 'is' : 'are').' left in stock. Please adjust your cart.'
                            : $product->name.' is currently sold out. Please remove it and try again.',
                    ]);
                }
            }

            $subtotal = round($subtotal, 2);
            $promotion = null;
            $discount = 0.0;

            if (! empty($data['promotion_code'])) {
                $promotion = Promotion::activeNow()
                    ->where('code', strtoupper(trim($data['promotion_code'])))
                    ->lockForUpdate()
                    ->first();

                if (! $promotion) {
                    throw ValidationException::withMessages([
                        'promotion_code' => 'That promotion code is invalid or has expired.',
                    ]);
                }

                if ($promotion->isExhausted()) {
                    throw ValidationException::withMessages([
                        'promotion_code' => 'That promotion has reached its usage limit.',
                    ]);
                }

                if ($promotion->hasReachedPerCustomerLimit($user)) {
                    throw ValidationException::withMessages([
                        'promotion_code' => 'You have already used this promotion the maximum number of times.',
                    ]);
                }

                if (! $promotion->appliesTo($subtotal)) {
                    throw ValidationException::withMessages([
                        'promotion_code' => 'Your subtotal does not meet the minimum spend for this promotion.',
                    ]);
                }

                $categorySubtotal = 0.0;

                if ($promotion->applies_to === Promotion::APPLIES_CATEGORY) {
                    foreach ($lineItems as $line) {
                        if ((int) $line['product']->category_id === (int) $promotion->category_id) {
                            $categorySubtotal += $line['quote']['line_total'];
                        }
                    }
                }

                $eligibleSubtotal = $promotion->eligibleSubtotal($subtotal, $categorySubtotal);

                if ($eligibleSubtotal <= 0) {
                    throw ValidationException::withMessages([
                        'promotion_code' => 'This promotion does not apply to the items in your cart.',
                    ]);
                }

                $discount = $promotion->discountFor($subtotal, $eligibleSubtotal);
            }

            $total = round(max(0, $subtotal - $discount), 2);
            $isPickup = $data['service_type'] === Order::SERVICE_PICKUP;
            $pickupAt = $isPickup && ! empty($data['pickup_at'])
                ? Carbon::parse($data['pickup_at'])
                : null;
            $paymentMethod = $data['payment_method'] ?? ($isPickup ? Payment::METHOD_QR : Payment::METHOD_CASH);
            $requiredPayment = round($this->settings->requiredPaymentFor($data['service_type'], $total), 2);

            do {
                $orderNumber = Order::generateOrderNumber();
            } while (Order::where('order_number', $orderNumber)->exists());

            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'service_type' => $data['service_type'],
                'people_count' => $data['service_type'] === Order::SERVICE_DINE_IN ? ($data['people_count'] ?? null) : null,
                'pickup_name' => $isPickup ? ($data['pickup_name'] ?? null) : null,
                'pickup_phone' => $isPickup ? ($data['pickup_phone'] ?? null) : null,
                'pickup_at' => $pickupAt,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'promotion_id' => $promotion?->id,
                'promotion_code' => $promotion?->code,
                'status' => OrderStatus::PendingPayment,
                'payment_method' => $paymentMethod,
                'payment_status' => $requiredPayment > 0 ? Order::PAYMENT_PENDING : Order::PAYMENT_UNPAID,
                'required_payment_amount' => $requiredPayment,
                'amount_paid' => 0,
            ]);

            foreach ($lineItems as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'category_name' => $line['product']->category?->name,
                    'quantity' => $line['quote']['quantity'],
                    'note' => $line['note'],
                    'unit_price' => $line['quote']['unit_price'],
                    'line_total' => $line['quote']['line_total'],
                    'options_snapshot' => $line['quote']['options'],
                    'addons_snapshot' => $line['quote']['addons'],
                ]);
            }

            foreach ($productQuantities as $productId => $quantity) {
                $product = $lockedProducts[$productId];

                if ($product->tracksStock()) {
                    $product->update(['stock_quantity' => $product->stock_quantity - $quantity]);
                }
            }

            if ($promotion) {
                $promotion->recordRedemption($order, $user, $discount);
            }

            if ($requiredPayment > 0) {
                $proofPath = ($data['payment_proof'] ?? null) instanceof UploadedFile
                    ? $this->paymentProofs->store($data['payment_proof'])
                    : null;

                $order->payments()->create([
                    'method' => $paymentMethod,
                    'type' => $requiredPayment + 0.001 >= $total ? Payment::TYPE_FULL : Payment::TYPE_DEPOSIT,
                    'amount' => $requiredPayment,
                    'status' => Payment::STATUS_PENDING,
                    'reference' => $data['payment_reference'] ?? null,
                    'proof_path' => $proofPath,
                ]);
            }

            $order->statusHistories()->create([
                'status' => OrderStatus::PendingPayment->value,
                'note' => $requiredPayment > 0
                    ? 'Order placed. Awaiting verification of ₱'.number_format($requiredPayment, 2).' '.($paymentMethod === Payment::METHOD_QR ? 'GCash / QR' : 'cash').' payment.'
                    : 'Order placed by customer.',
                'changed_by' => $user->id,
            ]);

            $this->statusNotifier->notify($order, OrderStatus::PendingPayment);

            if ($requiredPayment > 0) {
                User::query()
                    ->where('is_admin', true)
                    ->where('is_active', true)
                    ->get()
                    ->each(function (User $admin) use ($order, $requiredPayment, $paymentMethod) {
                        try {
                            $admin->notify(new AdminAlert(
                                title: 'New order awaiting payment',
                                message: 'Order '.$order->order_number.' was placed and needs ₱'.number_format($requiredPayment, 2).' '.($paymentMethod === Payment::METHOD_QR ? 'GCash / QR' : 'cash').' verification.',
                                url: route('admin.orders.show', $order),
                                orderNumber: $order->order_number,
                            ));
                        } catch (Throwable $exception) {
                            report($exception);
                        }
                    });
            } else {
                User::query()
                    ->where('is_admin', true)
                    ->where('is_active', true)
                    ->get()
                    ->each(function (User $admin) use ($order) {
                        try {
                            $admin->notify(new AdminAlert(
                                title: 'New order received',
                                message: 'Order '.$order->order_number.' was placed with no advance payment required.',
                                url: route('admin.orders.show', $order),
                                orderNumber: $order->order_number,
                            ));
                        } catch (Throwable $exception) {
                            report($exception);
                        }
                    });
            }

            $cart->items()->whereKey($items->modelKeys())->delete();

            if (! $cart->items()->exists()) {
                $cart->delete();
            }

            return $order->load(['items', 'statusHistories', 'payments']);
        }, 3);
    }

    public function updateStatus(Order $order, OrderStatus $status, ?string $note, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($order, $status, $note, $actor) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $previousStatus = $order->status;

            if (! $order->status->canTransitionTo($status)) {
                throw ValidationException::withMessages([
                    'status' => "Order cannot move from {$order->status->value} to {$status->value}.",
                ]);
            }

            if ($order->status === $status) {
                return $order;
            }

            if (in_array($status, [OrderStatus::Paid, OrderStatus::Preparing, OrderStatus::Ready], true)
                && ! $order->isClearedForPreparation()) {
                throw ValidationException::withMessages([
                    'status' => 'Verify the required payment before marking this order as '.$status->value.'.',
                ]);
            }

            $attributes = ['status' => $status];

            if ($status === OrderStatus::Preparing && $order->preparation_start_at === null) {
                $attributes['preparation_start_at'] = now();
            }

            if ($status === OrderStatus::Ready && $order->isPickup()) {
                $readyFrom = $order->pickup_at && $order->pickup_at->isFuture()
                    ? $order->pickup_at->copy()
                    : now();

                $attributes['pickup_deadline_at'] = $readyFrom->addMinutes($this->settings->deadlineMinutes());
            }

            if ($status === OrderStatus::Overdue) {
                $attributes['overdue_at'] = now();
            }

            if ($status === OrderStatus::Cancelled) {
                $order->restockItemsIfUnprepared();
                $this->waste->recordForCancellation($order, $actor, $note);
                $attributes['cancelled_at'] = now();
                $attributes['cancelled_reason'] = $note;
                $attributes['cancelled_by'] = $actor?->id;
            }

            $order->update($attributes);

            if ($status === OrderStatus::Cancelled) {
                $order->promotion?->releaseRedemption($order);
            }

            $order->statusHistories()->create([
                'status' => $status->value,
                'note' => $note ?: "Status updated to {$status->value}.",
                'changed_by' => $actor?->id,
            ]);

            $order->refresh();

            $this->statusNotifier->notify($order, $status, $previousStatus);

            return $order;
        }, 3);
    }
}

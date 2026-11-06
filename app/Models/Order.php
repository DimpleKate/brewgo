<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    public const SERVICE_DINE_IN = 'dine_in';

    public const SERVICE_PICKUP = 'pickup';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PENDING = 'pending_verification';

    public const PAYMENT_DEPOSIT = 'deposit_paid';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_REFUNDED = 'refunded';

    protected $fillable = [
        'order_number',
        'user_id',
        'service_type',
        'people_count',
        'pickup_name',
        'pickup_phone',
        'pickup_at',
        'preparation_start_at',
        'pickup_deadline_at',
        'notes',
        'subtotal',
        'discount',
        'total',
        'promotion_id',
        'promotion_code',
        'status',
        'payment_method',
        'payment_status',
        'required_payment_amount',
        'amount_paid',
        'paid_at',
        'overdue_at',
        'cancelled_at',
        'cancelled_reason',
        'cancelled_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'required_payment_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'status' => OrderStatus::class,
            'pickup_phone' => 'string',
            'pickup_at' => 'datetime',
            'preparation_start_at' => 'datetime',
            'pickup_deadline_at' => 'datetime',
            'paid_at' => 'datetime',
            'overdue_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function requests(): HasMany
    {
        return $this->hasMany(OrderRequest::class)->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class)->latest('issued_at');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function wasteItems(): HasMany
    {
        return $this->hasMany(OrderWasteItem::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === OrderStatus::Completed;
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isDineIn(): bool
    {
        return $this->service_type === self::SERVICE_DINE_IN;
    }

    public function isPickup(): bool
    {
        return $this->service_type === self::SERVICE_PICKUP;
    }

    public function canBeCancelledByCustomer(): bool
    {
        return in_array($this->status, [
            OrderStatus::PendingPayment,
            OrderStatus::Pending,
            OrderStatus::Confirmed,
            OrderStatus::Paid,
        ], true)
            && ! $this->requests()->where('status', OrderRequest::STATUS_PENDING)->exists();
    }

    public function canBeRefundedByCustomer(): bool
    {
        return (float) $this->amount_paid > 0
            && in_array($this->status, [
                OrderStatus::Completed,
                OrderStatus::Cancelled,
                OrderStatus::Rejected,
                OrderStatus::Overdue,
                OrderStatus::Ready,
            ], true)
            && ! $this->requests()->where('status', OrderRequest::STATUS_PENDING)->exists();
    }

    public function canBeCancelledImmediately(): bool
    {
        return in_array($this->status, [
            OrderStatus::PendingPayment,
            OrderStatus::Pending,
            OrderStatus::Confirmed,
            OrderStatus::Paid,
        ], true);
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }

    public function statusBadgeClass(): string
    {
        return $this->status->badgeClass();
    }

    public function serviceLabel(): string
    {
        return $this->isDineIn() ? 'Dine-in' : 'Pickup';
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_PENDING => 'Awaiting verification',
            self::PAYMENT_DEPOSIT => 'Deposit verified',
            self::PAYMENT_PAID => 'Paid in full',
            self::PAYMENT_REFUNDED => 'Refunded',
            default => 'Unpaid',
        };
    }

    public function paymentStatusBadgeClass(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_PAID => 'text-bg-success',
            self::PAYMENT_DEPOSIT => 'text-bg-info',
            self::PAYMENT_PENDING => 'text-bg-warning',
            self::PAYMENT_REFUNDED => 'text-bg-secondary',
            default => 'text-bg-danger',
        };
    }

    public function verifiedPaymentTotal(): float
    {
        if ($this->relationLoaded('payments')) {
            return round((float) $this->payments
                ->where('status', Payment::STATUS_VERIFIED)
                ->sum('amount'), 2);
        }

        return round((float) $this->payments()
            ->where('status', Payment::STATUS_VERIFIED)
            ->sum('amount'), 2);
    }

    public function isClearedForPreparation(): bool
    {
        $required = (float) $this->required_payment_amount;

        if ($required <= 0) {
            return true;
        }

        return (float) $this->amount_paid >= $required;
    }

    public function balanceDue(): float
    {
        return round(max(0, (float) $this->total - (float) $this->amount_paid), 2);
    }

    public function isOverdue(): bool
    {
        return $this->status === OrderStatus::Overdue;
    }

    /**
     * Return tracked stock to the catalog when an order is cancelled before preparation.
     */
    public function restockItemsIfUnprepared(): void
    {
        if (in_array($this->status, [
            OrderStatus::Preparing,
            OrderStatus::Ready,
            OrderStatus::Overdue,
        ], true)) {
            return;
        }

        $this->loadMissing('items.product');

        foreach ($this->items as $item) {
            if ($item->product?->tracksStock()) {
                $item->product->increment('stock_quantity', $item->quantity);
            }
        }
    }

    public static function generateOrderNumber(): string
    {
        return 'BG-'.now()->format('ymd').'-'.strtoupper(str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT));
    }
}

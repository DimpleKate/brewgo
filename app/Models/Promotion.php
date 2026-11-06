<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Promotion extends Model
{
    use HasFactory;

    public const APPLIES_ALL = 'all';

    public const APPLIES_CATEGORY = 'category';

    protected $fillable = [
        'title',
        'description',
        'code',
        'discount_type',
        'discount_value',
        'minimum_spend',
        'usage_limit',
        'per_customer_limit',
        'used_count',
        'applies_to',
        'category_id',
        'max_discount',
        'starts_at',
        'ends_at',
        'is_active',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'minimum_spend' => 'decimal:2',
            'usage_limit' => 'integer',
            'per_customer_limit' => 'integer',
            'used_count' => 'integer',
            'max_discount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(PromotionRedemption::class);
    }

    public function scopeActiveNow(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function isExhausted(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    public function hasReachedPerCustomerLimit(User $user): bool
    {
        if ($this->per_customer_limit === null) {
            return false;
        }

        return $this->redemptions()
            ->where('user_id', $user->id)
            ->count() >= $this->per_customer_limit;
    }

    public function appliesTo(float $subtotal): bool
    {
        return $subtotal >= (float) $this->minimum_spend;
    }

    /**
     * The portion of the cart that the discount can be applied to.
     */
    public function eligibleSubtotal(float $subtotal, float $categorySubtotal = 0.0): float
    {
        if ($this->applies_to === self::APPLIES_CATEGORY) {
            return round(max(0, $categorySubtotal), 2);
        }

        return round(max(0, $subtotal), 2);
    }

    public function discountFor(float $subtotal, ?float $eligibleSubtotal = null): float
    {
        if (! $this->appliesTo($subtotal)) {
            return 0.0;
        }

        $eligible = $this->eligibleSubtotal($subtotal, $eligibleSubtotal ?? 0.0);

        if ($eligible <= 0) {
            return 0.0;
        }

        $discount = $this->discount_type === 'percent'
            ? $eligible * ((float) $this->discount_value / 100)
            : (float) $this->discount_value;

        if ($this->max_discount !== null) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return round(min($discount, $eligible), 2);
    }

    public function recordRedemption(Order $order, User $user, float $discount): PromotionRedemption
    {
        $this->increment('used_count');

        return $this->redemptions()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'discount_amount' => $discount,
        ]);
    }

    public function releaseRedemption(Order $order): void
    {
        $redemption = $this->redemptions()->where('order_id', $order->id)->first();

        if (! $redemption) {
            return;
        }

        $redemption->delete();
        $this->decrement('used_count');
        $this->refresh();
    }

    public function scopeLabel(): string
    {
        if ($this->applies_to === self::APPLIES_CATEGORY) {
            return $this->category?->name ?? 'Selected category';
        }

        return 'Entire order';
    }

    public function usageLabel(): string
    {
        if ($this->usage_limit === null) {
            return 'Unlimited uses';
        }

        return $this->used_count.' / '.$this->usage_limit.' used';
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return Str::startsWith($this->image, ['http://', 'https://'])
            ? $this->image
            : asset($this->image);
    }
}

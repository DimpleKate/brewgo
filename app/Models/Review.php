<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Review extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'order_id',
        'user_id',
        'rating',
        'comment',
        'status',
        'moderated_by',
        'moderated_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'moderated_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeForProduct(Builder $query, Product|int $product): Builder
    {
        $productId = $product instanceof Product ? $product->getKey() : $product;

        return $query->whereHas(
            'order.items',
            fn (Builder $items) => $items->where('product_id', $productId)
        );
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_HIDDEN => 'Hidden',
            default => 'Pending review',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'text-bg-success',
            self::STATUS_HIDDEN => 'text-bg-secondary',
            default => 'text-bg-warning',
        };
    }

    /**
     * Keep public review listings privacy-friendly by shortening surnames.
     */
    public function reviewerDisplayName(): string
    {
        $name = trim((string) $this->user?->name);

        if ($name === '') {
            return 'Brew & Go customer';
        }

        $parts = preg_split('/\s+/', $name) ?: [$name];
        $first = (string) $parts[0];
        $initial = count($parts) > 1 ? Str::upper(Str::substr((string) end($parts), 0, 1)) : '';

        return $initial !== '' ? "{$first} {$initial}." : $first;
    }
}

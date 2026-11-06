<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function getSubtotalAttribute(): float
    {
        return round($this->items
            ->where('is_selected', true)
            ->sum(fn (CartItem $item) => (float) $item->unit_price * $item->quantity), 2);
    }

    public function getTotalQuantityAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function getUniqueItemCountAttribute(): int
    {
        return $this->items->pluck('product_id')->unique()->count();
    }

    public function getSelectedTotalQuantityAttribute(): int
    {
        return (int) $this->items->where('is_selected', true)->sum('quantity');
    }

    public function getSelectedItemCountAttribute(): int
    {
        return $this->items->where('is_selected', true)->count();
    }

    public function getSelectedUniqueItemCountAttribute(): int
    {
        return $this->items->where('is_selected', true)->pluck('product_id')->unique()->count();
    }

    public function getHasSelectedItemsAttribute(): bool
    {
        return $this->items->contains(fn (CartItem $item) => (bool) $item->is_selected);
    }
}

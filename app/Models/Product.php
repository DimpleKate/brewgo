<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'base_price',
        'image',
        'is_featured',
        'is_active',
        'stock_quantity',
        'low_stock_threshold',
        'is_sold_out',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_sold_out' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('group_name')->orderBy('sort_order');
    }

    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class, 'product_addon');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('is_sold_out', false)
            ->where(fn (Builder $q) => $q
                ->whereNull('stock_quantity')
                ->orWhere('stock_quantity', '>', 0));
    }

    public function tracksStock(): bool
    {
        return $this->stock_quantity !== null;
    }

    public function isSoldOut(): bool
    {
        return (bool) $this->is_sold_out
            || ($this->tracksStock() && $this->stock_quantity <= 0);
    }

    public function isOrderable(): bool
    {
        return $this->is_active && ! $this->isSoldOut();
    }

    public function hasStockFor(int $quantity): bool
    {
        if (! $this->tracksStock()) {
            return true;
        }

        return $this->stock_quantity >= max(0, $quantity);
    }

    public function stockLabel(): string
    {
        if ($this->is_sold_out) {
            return 'Sold out';
        }

        if (! $this->tracksStock()) {
            return 'Stock not tracked';
        }

        if ($this->stock_quantity <= 0) {
            return 'Sold out';
        }

        return $this->stock_quantity.' in stock';
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

    public function requiresSizeSelection(): bool
    {
        $this->loadMissing('options');

        return $this->options->contains(
            fn (ProductOption $option) => $option->group_name === 'size'
                && $option->is_active
        );
    }
}

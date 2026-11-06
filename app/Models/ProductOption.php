<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    use HasFactory;

    public const GROUPS = [
        'size' => 'Size',
        'flavor' => 'Flavor',
        'sweetness' => 'Sweetness',
        'ice' => 'Ice Level',
    ];

    public const SIZES = ['Small', 'Medium', 'Large'];

    protected $fillable = [
        'product_id',
        'group_name',
        'name',
        'price_adjustment',
        'price',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_adjustment' => 'decimal:2',
            'price' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function groupLabel(): string
    {
        return self::GROUPS[$this->group_name] ?? ucfirst($this->group_name);
    }
}

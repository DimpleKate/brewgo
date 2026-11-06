<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Support\Facades\DB;

class ProductSizePriceService
{
    /**
     * @param  array{small: int|float|string, medium: int|float|string, large: int|float|string}  $prices
     */
    public function sync(Product $product, array $prices): void
    {
        DB::transaction(function () use ($product, $prices) {
            $existingSizes = $product->options()
                ->where('group_name', 'size')
                ->get()
                ->keyBy(fn (ProductOption $option) => strtolower($option->name));

            foreach (ProductOption::SIZES as $index => $name) {
                $attributes = [
                    'group_name' => 'size',
                    'name' => $name,
                    'price' => round((float) $prices[strtolower($name)], 2),
                    'price_adjustment' => 0,
                    'is_default' => false,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ];

                $option = $existingSizes->get(strtolower($name));

                if ($option) {
                    $option->update($attributes);
                } else {
                    $product->options()->create($attributes);
                }
            }

            $product->options()
                ->where('group_name', 'size')
                ->whereNotIn('name', ProductOption::SIZES)
                ->update([
                    'is_default' => false,
                    'is_active' => false,
                ]);
        });
    }
}

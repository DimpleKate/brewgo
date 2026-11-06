<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PricingService
{
    public function assertQuantity(int $quantity): void
    {
        if ($quantity < 1 || $quantity > 20) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be between 1 and 20.',
            ]);
        }
    }

    /**
     * Recalculate a cart line entirely from database prices.
     *
     * @param  array<int, int|string>  $optionIds
     * @param  array<int, int|string>  $addonIds
     * @return array{quantity:int, options:array, addons:array, unit_price:float, line_total:float}
     */
    public function quote(Product $product, array $optionIds, array $addonIds, int $quantity): array
    {
        $this->assertQuantity($quantity);

        $optionIds = $this->normalizeIds($optionIds);
        $addonIds = $this->normalizeIds($addonIds);

        $product->loadMissing(['options', 'addons']);

        $activeOptions = $product->options->where('is_active', true);
        $selectedOptions = $activeOptions->whereIn('id', $optionIds);

        if ($selectedOptions->count() !== count($optionIds)) {
            throw ValidationException::withMessages([
                'options' => 'One or more selected options are invalid for this product.',
            ]);
        }

        $selectedOptions->groupBy('group_name')->each(function (Collection $group) {
            if ($group->count() > 1) {
                throw ValidationException::withMessages([
                    'options' => 'Only one option may be selected per customization group.',
                ]);
            }
        });

        $activeOptions->groupBy('group_name')->each(function (Collection $group, string $groupName) use ($selectedOptions) {
            if ($selectedOptions->where('group_name', $groupName)->count() !== 1) {
                throw ValidationException::withMessages([
                    'options' => 'Please choose a '.strtolower($groupName).' option.',
                ]);
            }
        });

        $activeAddons = $product->addons->where('is_active', true);
        $selectedAddons = $activeAddons->whereIn('id', $addonIds);

        if ($selectedAddons->count() !== count($addonIds)) {
            throw ValidationException::withMessages([
                'addons' => 'One or more selected add-ons are invalid for this product.',
            ]);
        }

        $absoluteSizeOption = $selectedOptions->first(
            fn ($option) => $option->group_name === 'size' && $option->price !== null
        );

        $basePrice = $absoluteSizeOption ? (float) $absoluteSizeOption->price : (float) $product->base_price;
        $optionTotal = (float) $selectedOptions
            ->reject(fn ($option) => $absoluteSizeOption && $option->is($absoluteSizeOption))
            ->sum(fn ($option) => (float) $option->price_adjustment);
        $addonTotal = (float) $selectedAddons->sum(fn ($addon) => (float) $addon->price);
        $unitPrice = round($basePrice + $optionTotal + $addonTotal, 2);

        return [
            'quantity' => $quantity,
            'options' => $selectedOptions->map(fn ($option) => [
                'id' => $option->id,
                'group' => $option->group_name,
                'name' => $option->name,
                'price_adjustment' => (float) $option->price_adjustment,
                'price' => $option->price !== null ? (float) $option->price : null,
            ])->values()->all(),
            'addons' => $selectedAddons->map(fn ($addon) => [
                'id' => $addon->id,
                'name' => $addon->name,
                'price' => (float) $addon->price,
            ])->values()->all(),
            'unit_price' => $unitPrice,
            'line_total' => round($unitPrice * $quantity, 2),
        ];
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}

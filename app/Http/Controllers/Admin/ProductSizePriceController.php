<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProductSizePricesRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ProductSizePriceController extends Controller
{
    public function update(UpdateProductSizePricesRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $submittedIds = collect($data['sizes'])
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $existingOptions = $product->options()
            ->where('group_name', 'size')
            ->whereIn('id', $submittedIds)
            ->get()
            ->keyBy('id');

        abort_unless($submittedIds->count() === $existingOptions->count(), 404);

        DB::transaction(function () use ($data, $product) {
            $product->options()
                ->where('group_name', 'size')
                ->update(['is_default' => false]);

            foreach ($data['sizes'] as $index => $size) {
                $attributes = [
                    'group_name' => 'size',
                    'name' => trim($size['name']),
                    'price' => round((float) $size['price'], 2),
                    'price_adjustment' => 0,
                    'is_default' => (int) $data['default_size'] === $index,
                    'is_active' => (bool) $size['is_active'],
                    'sort_order' => $index + 1,
                ];

                if ($size['id']) {
                    $product->options()->whereKey($size['id'])->update($attributes);
                } else {
                    $product->options()->create($attributes);
                }
            }
        });

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Size prices updated.');
    }
}

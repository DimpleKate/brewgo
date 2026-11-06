<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductOptionRequest;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ProductOptionController extends Controller
{
    public function store(ProductOptionRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product) {
            $data = $request->validated();

            if ($data['is_default']) {
                $product->options()
                    ->where('group_name', $data['group_name'])
                    ->update(['is_default' => false]);
            }

            $product->options()->create($data);
        });

        return redirect()->route('admin.products.edit', $product)->with('success', 'Customization option added.');
    }

    public function update(ProductOptionRequest $request, Product $product, ProductOption $option): RedirectResponse
    {
        abort_unless($option->product_id === $product->id, 404);

        DB::transaction(function () use ($request, $product, $option) {
            $data = $request->validated();

            if ($data['is_default']) {
                $product->options()
                    ->where('group_name', $data['group_name'])
                    ->whereKeyNot($option->id)
                    ->update(['is_default' => false]);
            }

            $option->update($data);
        });

        return redirect()->route('admin.products.edit', $product)->with('success', 'Customization option updated.');
    }

    public function destroy(Product $product, ProductOption $option): RedirectResponse
    {
        abort_unless($option->product_id === $product->id, 404);
        $option->delete();

        return redirect()->route('admin.products.edit', $product)->with('success', 'Customization option removed.');
    }
}

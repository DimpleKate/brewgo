<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $categories = Category::query()
            ->active()
            ->ordered()
            ->get();

        $activeCategory = $categories->firstWhere(
            'slug',
            $request->string('category')->trim()->toString()
        );

        $products = Product::active()
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->with(['category', 'options'])
            ->orderBy('name')
            ->get();

        return view('menu.index', compact('products', 'search', 'categories', 'activeCategory'));
    }

    public function show(Product $product): View
    {
        if (! $product->is_active) {
            abort(404);
        }

        $product->load([
            'category',
            'options' => fn ($query) => $query->where('is_active', true),
            'addons' => fn ($query) => $query->where('is_active', true),
        ]);

        if (! $product->category?->is_active) {
            abort(404);
        }

        $optionGroups = $product->options->groupBy('group_name');

        if ($optionGroups->has('size')) {
            $optionGroups = collect(['size' => $optionGroups->pull('size')])->merge($optionGroups);
        }

        $reviews = Review::query()
            ->approved()
            ->forProduct($product)
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        $reviewCount = Review::query()
            ->approved()
            ->forProduct($product)
            ->count();

        $reviewAverage = $reviewCount > 0
            ? round((float) Review::query()->approved()->forProduct($product)->avg('rating'), 1)
            : null;

        return view('menu.show', compact('product', 'optionGroups', 'reviews', 'reviewCount', 'reviewAverage'));
    }
}

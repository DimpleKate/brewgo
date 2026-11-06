<?php

namespace App\Http\Controllers;

use App\Models\CarouselSlide;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\StorefrontVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $isCustomer = auth()->check();

        $featuredProducts = $isCustomer
            ? Product::active()
                ->featured()
                ->whereHas('category', fn ($query) => $query->where('is_active', true))
                ->with(['category', 'options'])
                ->orderBy('name')
                ->take(8)
                ->get()
            : collect();

        $promotions = Promotion::activeNow()
            ->orderByDesc('discount_value')
            ->take(3)
            ->get();

        $carouselSlides = CarouselSlide::active()->ordered()->get();

        return view('home', compact('featuredProducts', 'promotions', 'carouselSlides'));
    }

    public function version(StorefrontVersionService $versions): JsonResponse
    {
        return response()->json(['version' => $versions->current()]);
    }
}

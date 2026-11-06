<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Promotion;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\OrderSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
        private readonly OrderSettingsService $orderSettings
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $cart = $this->cartService->forUser($request->user());
        $this->cartService->consolidate($cart);

        if (! $cart->items()->exists()) {
            return redirect()->route('menu.index')->with('error', 'Your cart is empty. Add a drink or dish first.');
        }

        $selectedItems = $cart->items()
            ->where('is_selected', true)
            ->with(['product.category', 'product.options', 'product.addons'])
            ->get();

        if ($selectedItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Select at least one item to checkout.');
        }

        $selectedQuantities = $selectedItems
            ->groupBy('product_id')
            ->map(fn ($lines) => (int) $lines->sum('quantity'));

        $unavailable = $selectedItems->first(function ($item) use ($selectedQuantities) {
            $product = $item->product;

            return ! $product
                || ! $product->isOrderable()
                || ! $product->hasStockFor($selectedQuantities[$item->product_id] ?? 0);
        });

        if ($unavailable) {
            return redirect()
                ->route('cart.index')
                ->with('error', $unavailable->product->name.' is sold out or no longer has enough stock. Please adjust your cart.');
        }

        $cart->setRelation('items', $selectedItems);

        $promotions = Promotion::activeNow()->orderBy('minimum_spend')->get();
        $user = $request->user();

        $orderSettings = $this->orderSettings->all();
        $policies = $this->orderSettings->policies();
        $estimatedAdvance = $this->orderSettings->requiredAdvanceFor((float) $cart->subtotal);
        $minPickupAt = now()->addMinutes($this->orderSettings->minNoticeMinutes());
        $maxPickupAt = now()->addDays(7);

        return view('checkout.create', compact(
            'cart',
            'promotions',
            'user',
            'orderSettings',
            'policies',
            'estimatedAdvance',
            'minPickupAt',
            'maxPickupAt',
        ));
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $this->cartService->consolidate($this->cartService->forUser($request->user()));

        $order = $this->orderService->place($request->user(), $request->validated());

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Order '.$order->order_number.' has been placed.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function index(Request $request): View
    {
        $cart = $this->cartService->forUser($request->user());
        $this->cartService->consolidate($cart);
        $cart->load(['items.product.category', 'items.product.options', 'items.product.addons']);

        return view('cart.index', compact('cart'));
    }

    public function store(StoreCartItemRequest $request): RedirectResponse|JsonResponse
    {
        $product = Product::active()->findOrFail($request->integer('product_id'));
        $quickAdd = $request->boolean('quick_add');

        if ($quickAdd && $product->requiresSizeSelection()) {
            throw ValidationException::withMessages([
                'options' => 'Please choose a size before adding this drink to your cart.',
            ]);
        }

        $options = $quickAdd
            ? $this->cartService->defaultOptionIds($product)
            : $request->input('options', []);

        $item = $this->cartService->add($request->user(), $product, [
            'quantity' => $request->integer('quantity'),
            'note' => $request->input('note'),
            'options' => $options,
            'addons' => $quickAdd ? [] : $request->input('addons', []),
        ]);

        $message = $product->name.' was added to your cart.';

        if ($request->expectsJson()) {
            $cart = $this->cartService->forUser($request->user());
            $cart->load('items');

            return response()->json([
                'message' => $message,
                'item' => [
                    'id' => $item->id,
                    'quantity' => (int) $item->quantity,
                    'is_selected' => (bool) $item->is_selected,
                    'line_total' => (float) $item->line_total,
                    'line_total_formatted' => '₱'.number_format($item->line_total, 2),
                ],
                'cart' => $this->cartPayload($cart),
            ]);
        }

        return redirect()->route('cart.index')->with('success', $message);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $this->authorizeItem($request, $cartItem);

        $item = $this->cartService->update($cartItem, [
            'quantity' => $request->integer('quantity'),
            'options' => $request->input('options', []),
            'addons' => $request->input('addons', []),
        ]);

        if ($request->expectsJson()) {
            $cart = $this->cartService->forUser($request->user());
            $cart->load('items');

            return response()->json([
                'message' => 'Cart updated.',
                'item' => [
                    'id' => $item->id,
                    'quantity' => (int) $item->quantity,
                    'is_selected' => (bool) $item->is_selected,
                    'line_total' => (float) $item->line_total,
                    'line_total_formatted' => '₱'.number_format($item->line_total, 2),
                ],
                'cart' => $this->cartPayload($cart),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated.');
    }

    public function updateSelection(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $this->authorizeItem($request, $cartItem);

        $validated = $request->validate([
            'is_selected' => ['required', 'boolean'],
        ]);

        $item = $this->cartService->setSelected($cartItem, (bool) $validated['is_selected']);

        if ($request->expectsJson()) {
            $cart = $this->cartService->forUser($request->user());
            $cart->load('items');

            return response()->json([
                'message' => $item->is_selected ? 'Item selected for checkout.' : 'Item kept in your cart for later.',
                'item' => [
                    'id' => $item->id,
                    'is_selected' => (bool) $item->is_selected,
                ],
                'cart' => $this->cartPayload($cart),
            ]);
        }

        return redirect()->route('cart.index')->with(
            'success',
            $item->is_selected ? 'Item selected for checkout.' : 'Item kept in your cart for later.'
        );
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorizeItem($request, $cartItem);

        $this->cartService->remove($cartItem);

        return redirect()->route('cart.index')->with('success', 'Item removed from cart.');
    }

    /**
     * @return array<string, mixed>
     */
    private function cartPayload(Cart $cart): array
    {
        $uniqueItemCount = $cart->unique_item_count;

        $selectedUniqueItemCount = $cart->selected_unique_item_count;

        return [
            'subtotal' => (float) $cart->subtotal,
            'subtotal_formatted' => '₱'.number_format($cart->subtotal, 2),
            'total_quantity' => $cart->total_quantity,
            'total_quantity_label' => $cart->total_quantity.' '.Str::plural('item', $cart->total_quantity),
            'unique_item_count' => $uniqueItemCount,
            'unique_item_count_label' => $uniqueItemCount.' '.Str::plural('product', $uniqueItemCount),
            'selected_item_count' => $cart->selected_item_count,
            'selected_total_quantity' => $cart->selected_total_quantity,
            'selected_unique_item_count' => $selectedUniqueItemCount,
            'selected_unique_item_count_label' => $selectedUniqueItemCount.' '.Str::plural('product', $selectedUniqueItemCount),
            'has_selection' => $cart->has_selected_items,
        ];
    }

    private function authorizeItem(Request $request, CartItem $cartItem): void
    {
        abort_unless($cartItem->cart()->where('user_id', $request->user()->id)->exists(), 404);
    }
}

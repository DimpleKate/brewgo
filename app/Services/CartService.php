<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const MAX_ITEM_QUANTITY = 20;

    public function __construct(private readonly PricingService $pricing) {}

    public function forUser(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * @return array<int, int>
     */
    public function defaultOptionIds(Product $product): array
    {
        $product->loadMissing('options');

        return $product->options
            ->where('is_active', true)
            ->groupBy('group_name')
            ->map(fn ($group) => ($group->firstWhere('is_default', true) ?? $group->first())->id)
            ->values()
            ->all();
    }

    /**
     * @param  array{quantity:int, note?:string|null, options?:array, addons?:array}  $data
     */
    public function add(User $user, Product $product, array $data): CartItem
    {
        $this->assertOrderable($product);

        $quote = $this->pricing->quote(
            $product,
            $data['options'] ?? [],
            $data['addons'] ?? [],
            (int) $data['quantity']
        );

        $note = $this->normalizeNote($data['note'] ?? null);
        $options = $this->normalizeIds(array_column($quote['options'], 'id'));
        $addons = $this->normalizeIds(array_column($quote['addons'], 'id'));
        $signature = $this->lineSignature($product->id, $options, $addons, $note);

        return DB::transaction(function () use ($user, $product, $quote, $note, $options, $addons, $signature) {
            $cart = Cart::where('user_id', $user->id)->lockForUpdate()->first()
                ?? Cart::create(['user_id' => $user->id]);

            $matches = $cart->items()
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->get()
                ->filter(fn (CartItem $item) => $this->lineSignature(
                    $item->product_id,
                    $item->options ?? [],
                    $item->addons ?? [],
                    $item->note
                ) === $signature)
                ->values();

            $quantity = (int) $matches->sum('quantity') + $quote['quantity'];
            $productTotal = (int) $cart->items()->where('product_id', $product->id)->sum('quantity') + $quote['quantity'];

            if ($quantity > self::MAX_ITEM_QUANTITY) {
                throw ValidationException::withMessages([
                    'quantity' => 'You can have up to '.self::MAX_ITEM_QUANTITY.' of the same item in your cart.',
                ]);
            }

            $this->assertStock($product, $productTotal);

            $item = $matches->shift();

            if (! $item) {
                return $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'is_selected' => true,
                    'note' => $note,
                    'options' => $options,
                    'addons' => $addons,
                    'unit_price' => $quote['unit_price'],
                ]);
            }

            $item->update([
                'quantity' => $quantity,
                'is_selected' => true,
                'note' => $note,
                'options' => $options,
                'addons' => $addons,
                'unit_price' => $quote['unit_price'],
            ]);

            $matches->each->delete();

            return $item->refresh();
        });
    }

    /**
     * @param  array{quantity:int, options?:array, addons?:array}  $data
     */
    public function update(CartItem $item, array $data): CartItem
    {
        $item->loadMissing('product.options', 'product.addons', 'cart');

        $this->assertOrderable($item->product);

        $quote = $this->pricing->quote(
            $item->product,
            $data['options'] ?? [],
            $data['addons'] ?? [],
            (int) $data['quantity']
        );

        $otherQuantity = (int) $item->cart->items()
            ->where('product_id', $item->product_id)
            ->whereKeyNot($item->id)
            ->sum('quantity');

        $this->assertStock($item->product, $otherQuantity + $quote['quantity']);

        $item->update([
            'quantity' => $quote['quantity'],
            'options' => array_column($quote['options'], 'id'),
            'addons' => array_column($quote['addons'], 'id'),
            'unit_price' => $quote['unit_price'],
        ]);

        return $item->refresh();
    }

    public function setSelected(CartItem $item, bool $isSelected): CartItem
    {
        $item->update(['is_selected' => $isSelected]);

        return $item->refresh();
    }

    public function consolidate(Cart $cart): void
    {
        DB::transaction(function () use ($cart) {
            $groups = $cart->items()
                ->lockForUpdate()
                ->get()
                ->groupBy(fn (CartItem $item) => $this->lineSignature(
                    $item->product_id,
                    $item->options ?? [],
                    $item->addons ?? [],
                    $item->note
                ).'|'.($item->is_selected ? '1' : '0'));

            foreach ($groups as $items) {
                if ($items->count() < 2 || $items->pluck('unit_price')->unique()->count() > 1) {
                    continue;
                }

                $quantity = (int) $items->sum('quantity');

                if ($quantity > self::MAX_ITEM_QUANTITY) {
                    continue;
                }

                $item = $items->first();
                $item->update([
                    'quantity' => $quantity,
                    'is_selected' => $items->contains(fn (CartItem $line) => (bool) $line->is_selected),
                    'note' => $this->normalizeNote($item->note),
                    'options' => $this->normalizeIds($item->options ?? []),
                    'addons' => $this->normalizeIds($item->addons ?? []),
                ]);

                $items->slice(1)->each->delete();
            }
        });
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }

    private function lineSignature(int $productId, array $optionIds, array $addonIds, mixed $note): string
    {
        return (string) json_encode([
            $productId,
            $this->normalizeIds($optionIds),
            $this->normalizeIds($addonIds),
            $this->normalizeNote($note),
        ]);
    }

    private function assertOrderable(Product $product): void
    {
        if (! $product->isOrderable()) {
            throw ValidationException::withMessages([
                'quantity' => $product->name.' is currently sold out.',
            ]);
        }
    }

    private function assertStock(Product $product, int $quantity): void
    {
        if (! $product->hasStockFor($quantity)) {
            $available = max(0, (int) $product->stock_quantity);

            throw ValidationException::withMessages([
                'quantity' => $available > 0
                    ? 'Only '.$available.' '.$product->name.' '.($available === 1 ? 'is' : 'are').' left in stock.'
                    : $product->name.' is currently sold out.',
            ]);
        }
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
            ->sort()
            ->values()
            ->all();
    }

    private function normalizeNote(mixed $note): ?string
    {
        if ($note === null) {
            return null;
        }

        $note = trim((string) $note);

        return $note === '' ? null : $note;
    }
}

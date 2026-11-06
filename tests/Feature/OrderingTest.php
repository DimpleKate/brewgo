<?php

namespace Tests\Feature;

use App\Models\Addon;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderRequest;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seedTestCatalog();
        $this->createCustomer();
    }

    public function test_cart_requires_every_active_option_group(): void
    {
        $customer = $this->customer();
        $product = $this->signature();

        $this->actingAs($customer)
            ->from('/menu/'.$product->slug)
            ->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertRedirect('/menu/'.$product->slug)
            ->assertSessionHasErrors('options');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_cart_uses_server_side_prices_and_ignores_client_totals(): void
    {
        $customer = $this->customer();
        $product = $this->signature();
        $oatMilk = Addon::where('name', 'Oat Milk')->firstOrFail();

        $this->actingAs($customer)
            ->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
                'options' => $this->defaultOptions($product),
                'addons' => [$oatMilk->id],
                'unit_price' => 1,
                'line_total' => 1,
            ])
            ->assertRedirect(route('cart.index'));

        $item = Cart::where('user_id', $customer->id)->firstOrFail()->items()->firstOrFail();

        $this->assertSame(2, $item->quantity);
        $this->assertSame(200.0, (float) $item->unit_price);
        $this->assertContains($oatMilk->id, $item->addons);
        $this->get('/cart')->assertOk()->assertSee('Oat Milk');
    }

    public function test_product_card_quick_add_uses_defaults_merges_lines_and_counts_unique_products(): void
    {
        $customer = $this->customer();
        $category = Category::where('slug', 'coffee')->firstOrFail();

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Quick Add One',
            'slug' => 'quick-add-one',
            'description' => 'A product without size choices.',
            'base_price' => 90,
            'is_active' => true,
        ]);
        $secondProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'Quick Add Two',
            'slug' => 'quick-add-two',
            'description' => 'Another product without size choices.',
            'base_price' => 110,
            'is_active' => true,
        ]);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->actingAs($customer)->postJson('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
                'quick_add' => 1,
            ])->assertOk()
                ->assertJsonPath('cart.unique_item_count', 1)
                ->assertJsonPath('cart.total_quantity', $attempt + 1);
        }

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
            'addons' => json_encode([]),
        ]);

        $this->actingAs($customer)->postJson('/cart/items', [
            'product_id' => $secondProduct->id,
            'quantity' => 2,
            'quick_add' => 1,
        ])->assertOk()
            ->assertJsonPath('cart.unique_item_count', 2)
            ->assertJsonPath('cart.unique_item_count_label', '2 products')
            ->assertJsonPath('cart.total_quantity', 5);

        $cart = Cart::where('user_id', $customer->id)->firstOrFail();
        $this->assertSame(2, $cart->unique_item_count);
        $this->assertSame(5, $cart->total_quantity);

        $this->get('/menu')
            ->assertOk()
            ->assertSee('aria-label="Cart, 2 products"', false)
            ->assertSee('data-cart-count', false)
            ->assertSee('Add to Cart')
            ->assertDontSee('Customize');

        $item = $cart->items()->where('product_id', $product->id)->firstOrFail();

        $this->assertEqualsCanonicalizing($this->defaultOptions($product), $item->options);

        $this->delete(route('cart.items.destroy', $item))
            ->assertRedirect(route('cart.index'));

        $this->assertSame(1, $cart->items()->count());

        $this->actingAs($customer->fresh())
            ->get('/menu')
            ->assertOk()
            ->assertSee('aria-label="Cart, 1 product"', false);
    }

    public function test_selected_absolute_size_sets_product_cart_checkout_and_order_totals(): void
    {
        $customer = $this->customer();
        $product = Product::with(['options', 'addons'])->where('slug', 'iced-americano')->firstOrFail();

        $product->options()
            ->where('group_name', 'size')
            ->where('name', 'Regular')
            ->update(['price' => 40]);
        $product->options()
            ->where('group_name', 'size')
            ->where('name', 'Large')
            ->update(['price' => 75]);

        $product->load('options');

        $regularOptions = $this->defaultOptions($product);
        $largeOptions = $product->options
            ->where('is_active', true)
            ->groupBy('group_name')
            ->map(function ($group, string $groupName) {
                $option = $groupName === 'size'
                    ? $group->firstWhere('name', 'Large')
                    : ($group->firstWhere('is_default', true) ?? $group->first());

                return $option->id;
            })
            ->values()
            ->all();

        $this->actingAs($customer)
            ->get('/menu/'.$product->slug)
            ->assertOk()
            ->assertDontSee('From')
            ->assertDontSee('depending on size')
            ->assertSee('Regular')
            ->assertSee('Large')
            ->assertSee('₱40.00')
            ->assertSee('₱75.00');

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'options' => $regularOptions,
        ])->assertRedirect(route('cart.index'));

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'options' => $largeOptions,
        ])->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 40,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 75,
        ]);

        $cart = Cart::where('user_id', $customer->id)->firstOrFail();
        $this->assertSame(155.0, (float) $cart->subtotal);

        $this->actingAs($customer)
            ->get('/cart')
            ->assertOk()
            ->assertSee('₱155.00')
            ->assertSee('Regular (₱40.00)')
            ->assertSee('Large (₱75.00)');

        $this->actingAs($customer)
            ->get('/checkout')
            ->assertOk()
            ->assertSee('₱155.00');

        $this->actingAs($customer)->post('/checkout', [
            'service_type' => 'dine_in',
            'people_count' => 2,
        ])->assertRedirect();

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame(155.0, (float) $order->subtotal);
        $this->assertSame(155.0, (float) $order->total);
    }

    public function test_customers_choose_small_medium_or_large_and_the_selected_size_flows_to_cart_and_order(): void
    {
        $customer = $this->customer();
        $product = Product::with(['options', 'addons'])->where('slug', 'iced-americano')->firstOrFail();

        $small = $product->options->where('group_name', 'size')->firstWhere('name', 'Regular');
        $large = $product->options->where('group_name', 'size')->firstWhere('name', 'Large');

        $small->update([
            'name' => 'Small',
            'price' => 70,
            'price_adjustment' => 0,
            'is_default' => false,
            'sort_order' => 1,
        ]);
        $large->update([
            'name' => 'Large',
            'price' => 100,
            'price_adjustment' => 0,
            'is_default' => false,
            'sort_order' => 3,
        ]);

        $medium = $product->options()->create([
            'group_name' => 'size',
            'name' => 'Medium',
            'price' => 85,
            'price_adjustment' => 0,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $product->load('options');

        $response = $this->actingAs($customer)
            ->get('/menu/'.$product->slug)
            ->assertOk()
            ->assertSeeInOrder(['Small', '₱70.00', 'Medium', '₱85.00', 'Large', '₱100.00'])
            ->assertSee('data-price="70"', false)
            ->assertSee('data-price="85"', false)
            ->assertSee('data-price="100"', false)
            ->assertSee('data-price-mode="absolute"', false)
            ->assertSee('data-requires-size="1"', false)
            ->assertSee('data-size-prompt', false)
            ->assertSee('Select a size to continue.')
            ->assertSee('data-customizer-unit>Select a size</div>', false);

        $html = $response->getContent();

        $this->assertSame(0, preg_match('/data-size-option[^>]*checked/', $html));
        $this->assertMatchesRegularExpression('/<button[^>]*data-add-to-cart[^>]*disabled/', $html);

        $this->actingAs($customer)->postJson('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'quick_add' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('options');

        $optionsForSize = function (int $sizeId) use ($product): array {
            return $product->options
                ->where('is_active', true)
                ->groupBy('group_name')
                ->map(function ($group, string $groupName) use ($sizeId) {
                    $option = $groupName === 'size'
                        ? $group->firstWhere('id', $sizeId)
                        : ($group->firstWhere('is_default', true) ?? $group->first());

                    return $option->id;
                })
                ->values()
                ->all();
        };

        foreach ([$small->id => 70, $medium->id => 85, $large->id => 100] as $sizeId => $expectedUnitPrice) {
            $this->actingAs($customer)->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
                'options' => $optionsForSize($sizeId),
            ])->assertRedirect(route('cart.index'));

            $this->assertDatabaseHas('cart_items', [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $expectedUnitPrice,
            ]);
        }

        $this->actingAs($customer)
            ->get('/cart')
            ->assertOk()
            ->assertSeeInOrder([
                'Small (₱70.00)',
                'Medium (₱85.00)',
                'Large (₱100.00)',
            ])
            ->assertSee('₱255.00');

        $this->actingAs($customer)->post('/checkout', [
            'service_type' => 'dine_in',
            'people_count' => 1,
        ])->assertRedirect();

        $order = Order::with('items')->latest('id')->firstOrFail();

        $this->assertSame(255.0, (float) $order->total);

        $mediumLine = $order->items->first(fn ($item) => (float) $item->unit_price === 85.0);
        $this->assertNotNull($mediumLine);
        $this->assertSame('Medium', collect($mediumLine->options_snapshot)->firstWhere('group', 'size')['name']);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Medium (₱85.00)')
            ->assertSee('1 × ₱85.00');
    }

    public function test_adding_same_product_with_identical_customizations_merges_quantity(): void
    {
        $customer = $this->customer();
        $product = $this->signature();
        $oatMilk = Addon::where('name', 'Oat Milk')->firstOrFail();
        $options = $this->defaultOptions($product);

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'note' => '  Extra hot  ',
            'options' => $options,
            'addons' => [$oatMilk->id],
        ])->assertRedirect(route('cart.index'));

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'note' => 'Extra hot',
            'options' => array_reverse($options),
            'addons' => [$oatMilk->id],
        ])->assertRedirect(route('cart.index'));

        $cart = Cart::where('user_id', $customer->id)->firstOrFail();
        $items = $cart->items()->get();

        $this->assertCount(1, $items);

        $item = $items->first();
        $this->assertSame(3, $item->quantity);
        $this->assertSame('Extra hot', $item->note);
        $this->assertSame(200.0, (float) $item->unit_price);
        $this->assertSame(600.0, $item->line_total);

        $this->get('/cart')
            ->assertOk()
            ->assertSee('data-cart-item="'.$item->id.'"', false)
            ->assertSee('data-quantity="3"', false)
            ->assertSee('₱600.00');
    }

    public function test_same_product_with_different_customizations_addons_or_notes_stays_separate(): void
    {
        $customer = $this->customer();
        $product = $this->signature();
        $oatMilk = Addon::where('name', 'Oat Milk')->firstOrFail();
        $defaultOptions = $this->defaultOptions($product);
        $largeOptions = $product->options
            ->where('is_active', true)
            ->groupBy('group_name')
            ->map(function ($group, string $groupName) {
                $option = $groupName === 'size'
                    ? $group->firstWhere('name', 'Large')
                    : ($group->firstWhere('is_default', true) ?? $group->first());

                return $option->id;
            })
            ->values()
            ->all();

        foreach ([
            ['options' => $defaultOptions, 'addons' => [], 'note' => null],
            ['options' => $defaultOptions, 'addons' => [], 'note' => 'Extra hot'],
            ['options' => $largeOptions, 'addons' => [], 'note' => null],
            ['options' => $defaultOptions, 'addons' => [$oatMilk->id], 'note' => null],
        ] as $line) {
            $this->actingAs($customer)->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
                ...$line,
            ])->assertRedirect(route('cart.index'));
        }

        $this->assertDatabaseCount('cart_items', 4);

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'options' => $defaultOptions,
        ])->assertRedirect(route('cart.index'));

        $this->assertDatabaseCount('cart_items', 4);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
            'note' => null,
        ]);
    }

    public function test_existing_duplicate_cart_rows_are_consolidated_when_viewing_cart(): void
    {
        $customer = $this->customer();
        $product = $this->signature();
        $cart = Cart::firstOrCreate(['user_id' => $customer->id]);
        $options = $this->defaultOptions($product);

        foreach ([1, 2] as $quantity) {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'options' => $options,
                'addons' => [],
                'unit_price' => 165,
            ]);
        }

        $this->actingAs($customer)
            ->get('/cart')
            ->assertOk()
            ->assertSee('data-quantity="3"', false);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_merged_quantity_respects_the_maximum_item_quantity(): void
    {
        $customer = $this->customer();
        $product = $this->signature();

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 15,
            'options' => $this->defaultOptions($product),
        ])->assertRedirect(route('cart.index'));

        $this->actingAs($customer)
            ->from('/menu/'.$product->slug)
            ->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 6,
                'options' => $this->defaultOptions($product),
            ])
            ->assertRedirect('/menu/'.$product->slug)
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 15,
        ]);

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 5,
            'options' => $this->defaultOptions($product),
        ])->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 20,
        ]);

        $this->actingAs($customer)
            ->from('/menu/'.$product->slug)
            ->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
                'options' => $this->defaultOptions($product),
            ])
            ->assertRedirect('/menu/'.$product->slug)
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 20,
        ]);
    }

    public function test_merged_cart_item_reflects_in_checkout_and_order_summary(): void
    {
        $customer = $this->customer();
        $product = $this->signature();
        $options = $this->defaultOptions($product);

        foreach ([1, 2] as $quantity) {
            $this->actingAs($customer)->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'note' => 'Less ice, no sugar',
                'options' => $options,
            ])->assertRedirect(route('cart.index'));
        }

        $cart = Cart::where('user_id', $customer->id)->firstOrFail();
        $this->assertCount(1, $cart->items()->get());

        $this->get('/checkout')
            ->assertOk()
            ->assertSee('3× '.$product->name)
            ->assertSee('₱495.00');

        $this->post('/checkout', [
            'service_type' => 'dine_in',
            'people_count' => 3,
        ])->assertRedirect();

        $order = Order::latest()->firstOrFail();
        $items = $order->items()->get();

        $this->assertCount(1, $items);
        $this->assertSame(3, $items->first()->quantity);
        $this->assertSame('Less ice, no sugar', $items->first()->note);
        $this->assertSame(495.0, (float) $order->subtotal);
        $this->assertSame(495.0, (float) $order->total);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('3 × ₱165.00')
            ->assertSee('Less ice, no sugar');
    }

    public function test_cart_update_recalculates_from_database_prices(): void
    {
        $customer = $this->customer();
        $product = $this->signature();

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'options' => $this->defaultOptions($product),
        ]);

        $item = Cart::where('user_id', $customer->id)->firstOrFail()->items()->firstOrFail();

        $this->patch('/cart/items/'.$item->id, [
            'quantity' => 3,
            'options' => $this->defaultOptions($product),
            'addons' => [],
        ])->assertRedirect(route('cart.index'));

        $this->assertSame(3, $item->refresh()->quantity);
        $this->assertSame(495.0, $item->line_total);
    }

    public function test_cart_quantity_json_update_preserves_customizations_and_note(): void
    {
        $customer = $this->customer();
        $product = $this->signature();
        $oatMilk = Addon::where('name', 'Oat Milk')->firstOrFail();

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'note' => '  Extra hot  ',
            'options' => $this->defaultOptions($product),
            'addons' => [$oatMilk->id],
        ])->assertRedirect(route('cart.index'));

        $item = Cart::where('user_id', $customer->id)->firstOrFail()->items()->firstOrFail();

        $response = $this->actingAs($customer)->patchJson('/cart/items/'.$item->id, [
            'quantity' => 2,
            'options' => $this->defaultOptions($product),
            'addons' => [$oatMilk->id],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Cart updated.')
            ->assertJsonPath('item.quantity', 2)
            ->assertJsonPath('cart.total_quantity', 2)
            ->assertJsonPath('cart.total_quantity_label', '2 items')
            ->assertJsonPath('cart.unique_item_count', 1)
            ->assertJsonPath('cart.unique_item_count_label', '1 product');

        $this->assertSame(400.0, (float) $response->json('item.line_total'));
        $this->assertSame(400.0, (float) $response->json('cart.subtotal'));

        $item->refresh();

        $this->assertSame(2, $item->quantity);
        $this->assertSame('Extra hot', $item->note);
        $this->assertContains($oatMilk->id, $item->addons);
        $this->assertSame(400.0, $item->line_total);

        $this->get('/cart')
            ->assertOk()
            ->assertSee('data-cart-decrement', false)
            ->assertSee('data-cart-quantity-input', false)
            ->assertSee('name="quantity"', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('data-cart-increment', false)
            ->assertSee('Remove');

        foreach ([0, -1, 1.5, 21, 'abc', ''] as $invalidQuantity) {
            $this->actingAs($customer)
                ->patchJson('/cart/items/'.$item->id, [
                    'quantity' => $invalidQuantity,
                    'options' => $this->defaultOptions($product),
                    'addons' => [$oatMilk->id],
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors('quantity');
        }

        $this->assertSame(2, $item->refresh()->quantity);
    }

    public function test_cart_item_note_is_saved_displayed_and_preserved_when_quantity_updates(): void
    {
        $customer = $this->customer();
        $product = $this->signature();

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'note' => '  Less ice, no sugar  ',
            'options' => $this->defaultOptions($product),
        ])->assertRedirect(route('cart.index'));

        $item = Cart::where('user_id', $customer->id)->firstOrFail()->items()->firstOrFail();

        $this->assertSame('Less ice, no sugar', $item->note);
        $this->get('/cart')->assertOk()->assertSee('Less ice, no sugar');

        $this->patch('/cart/items/'.$item->id, [
            'quantity' => 3,
            'options' => $this->defaultOptions($product),
            'addons' => [],
        ])->assertRedirect(route('cart.index'));

        $item->refresh();

        $this->assertSame(3, $item->quantity);
        $this->assertSame('Less ice, no sugar', $item->note);
        $this->get('/cart')->assertOk()->assertSee('Less ice, no sugar');
    }

    public function test_item_note_is_optional_and_limited_to_255_characters(): void
    {
        $customer = $this->customer();
        $product = $this->signature();

        $this->actingAs($customer)
            ->from('/menu/'.$product->slug)
            ->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
                'note' => str_repeat('a', 256),
                'options' => $this->defaultOptions($product),
            ])
            ->assertRedirect('/menu/'.$product->slug)
            ->assertSessionHasErrors('note');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_size_options_without_absolute_prices_fall_back_to_base_price_and_adjustment(): void
    {
        $customer = $this->customer();
        $product = $this->signature();

        $product->options()->where('group_name', 'size')->update(['price' => null]);
        $product->load('options');

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'options' => $this->defaultOptions($product),
        ])->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 165,
        ]);

        $largeOptions = $product->options
            ->where('is_active', true)
            ->groupBy('group_name')
            ->map(function ($group, string $groupName) {
                $option = $groupName === 'size'
                    ? $group->firstWhere('name', 'Large')
                    : ($group->firstWhere('is_default', true) ?? $group->first());

                return $option->id;
            })
            ->values()
            ->all();

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'options' => $largeOptions,
        ])->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 195,
        ]);

        $this->get('/menu/'.$product->slug)
            ->assertOk()
            ->assertSee('Large')
            ->assertSee('+₱30.00');
    }

    public function test_another_customer_cannot_modify_a_cart_item(): void
    {
        $customer = $this->customer();
        $product = $this->signature();

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'options' => $this->defaultOptions($product),
        ]);

        $item = Cart::where('user_id', $customer->id)->firstOrFail()->items()->firstOrFail();
        $other = User::create([
            'name' => 'Other Customer',
            'email' => 'other@example.com',
            'password' => 'Otherpass1A',
            'is_admin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($other)
            ->patch('/cart/items/'.$item->id, [
                'quantity' => 2,
                'options' => $this->defaultOptions($product),
            ])
            ->assertNotFound();
    }

    public function test_checkout_creates_order_with_server_discount_and_clears_cart(): void
    {
        $customer = $this->customer();
        $product = $this->signature();

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'options' => $this->defaultOptions($product),
        ]);

        $response = $this->post('/checkout', [
            'service_type' => 'dine_in',
            'people_count' => 2,
            'notes' => 'Please make it extra hot.',
            'promotion_code' => 'golden10',
        ]);

        $order = Order::latest()->firstOrFail();
        $response->assertRedirect(route('orders.show', $order));

        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame(330.0, (float) $order->subtotal);
        $this->assertSame(33.0, (float) $order->discount);
        $this->assertSame(297.0, (float) $order->total);
        $this->assertSame('Pending Payment', $order->status->value);
        $this->assertSame(2, $order->people_count);
        $this->assertSame('GOLDEN10', $order->promotion_code);
        $this->assertSame(297.0, (float) $order->required_payment_amount);
        $this->assertSame(Order::PAYMENT_PENDING, $order->payment_status);
        $this->assertSame(1, $order->items()->count());
        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'Pending Payment',
        ]);

        $this->get(route('orders.show', $order))->assertOk()->assertSee($order->order_number);
    }

    public function test_customer_can_checkout_without_email_verification(): void
    {
        $customer = $this->customer();
        $product = $this->signature();

        $this->assertNull($customer->email_verified_at);

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'options' => $this->defaultOptions($product),
        ])->assertRedirect(route('cart.index'));

        $this->get('/checkout')
            ->assertOk()
            ->assertDontSee('Verify your email address');

        $this->post('/checkout', [
            'service_type' => 'dine_in',
            'people_count' => 1,
        ])->assertRedirect();

        $this->assertSame($customer->id, Order::latest()->firstOrFail()->user_id);
    }

    public function test_item_note_flows_to_checkout_order_and_admin_order(): void
    {
        $customer = $this->customer();
        $product = $this->signature();

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'note' => 'No whipped cream',
            'options' => $this->defaultOptions($product),
        ])->assertRedirect(route('cart.index'));

        $this->get('/checkout')->assertOk()->assertSee('No whipped cream');

        $this->post('/checkout', [
            'service_type' => 'dine_in',
            'people_count' => 1,
        ])->assertRedirect();

        $order = Order::latest()->firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->assertSame('No whipped cream', $item->note);
        $this->get(route('orders.show', $order))->assertOk()->assertSee('No whipped cream');

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('No whipped cream');
    }

    public function test_checkout_requires_pickup_contact_details(): void
    {
        $customer = $this->customer();
        $product = $this->signature();

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'options' => $this->defaultOptions($product),
        ]);

        $this->from('/checkout')->post('/checkout', [
            'service_type' => 'pickup',
        ])->assertRedirect('/checkout')->assertSessionHasErrors(['pickup_name', 'pickup_phone']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_can_request_cancellation_and_admin_can_approve(): void
    {
        $customer = $this->customer();
        $admin = $this->admin();
        $order = $this->placeBasicOrder($customer);

        $this->actingAs($customer)->post(route('orders.requests.store', $order), [
            'type' => 'cancellation',
            'reason' => 'I changed my mind about this order.',
        ])->assertRedirect(route('orders.show', $order));

        $request = OrderRequest::latest()->firstOrFail();
        $this->assertSame('pending', $request->status);

        $this->actingAs($admin)->patch(route('admin.requests.review', $request), [
            'decision' => 'approved',
            'admin_note' => 'Approved before preparation started.',
        ])->assertRedirect(route('admin.requests.index'));

        $this->assertSame('approved', $request->refresh()->status);
        $this->assertSame('Cancelled', $order->refresh()->status->value);
        $this->assertNotNull($request->reviewed_by);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'Cancelled',
        ]);
    }

    public function test_customer_can_request_refund_only_after_completion(): void
    {
        $customer = $this->customer();
        $order = $this->placeBasicOrder($customer);

        $this->actingAs($customer)
            ->post(route('orders.requests.store', $order), [
                'type' => 'refund',
                'reason' => 'I would like a refund for this order.',
            ])
            ->assertSessionHasErrors('type');

        $admin = $this->admin();

        $payment = $order->payments()->where('status', Payment::STATUS_PENDING)->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.orders.payments.verify', [$order, $payment]), [
            'note' => 'Cash verified at the cashier.',
        ])->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('Paid', $order->refresh()->status->value);
        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertNotNull($order->paid_at);

        foreach (['Preparing', 'Ready', 'Completed'] as $status) {
            $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
                'status' => $status,
            ])->assertRedirect(route('admin.orders.show', $order));
        }

        $this->assertSame('Completed', $order->refresh()->status->value);

        $this->actingAs($customer)->post(route('orders.requests.store', $order), [
            'type' => 'refund',
            'reason' => 'The completed order had a problem with one item.',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertSame('pending', OrderRequest::latest()->firstOrFail()->status);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $customer = $this->customer();
        $order = $this->placeBasicOrder($customer);

        $other = User::create([
            'name' => 'Other Customer',
            'email' => 'other@example.com',
            'password' => 'Otherpass1A',
            'is_admin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($other)->get(route('orders.show', $order))->assertForbidden();
        $this->actingAs($other)->get(route('orders.index'))->assertOk()->assertDontSee($order->order_number);
    }

    public function test_cart_item_selection_updates_selected_totals_and_checkout_state(): void
    {
        $customer = $this->customer();
        $product = $this->signature();
        $category = Category::where('slug', 'coffee')->firstOrFail();
        $secondProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'Selection Test Coffee',
            'slug' => 'selection-test-coffee',
            'description' => 'A product without size choices.',
            'base_price' => 90,
            'is_active' => true,
        ]);

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'options' => $this->defaultOptions($product),
        ])->assertRedirect(route('cart.index'));

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $secondProduct->id,
            'quantity' => 1,
            'quick_add' => 1,
        ])->assertRedirect(route('cart.index'));

        $cart = Cart::where('user_id', $customer->id)->firstOrFail();
        $secondItem = $cart->items()->where('product_id', $secondProduct->id)->firstOrFail();

        $this->assertSame(420.0, (float) $cart->subtotal);

        $this->actingAs($customer)
            ->get('/cart')
            ->assertOk()
            ->assertSee('data-cart-select', false)
            ->assertSee('₱420.00')
            ->assertSee('3 of 3 items');

        $this->actingAs($customer)
            ->patchJson(route('cart.items.selection', $secondItem), ['is_selected' => false])
            ->assertOk()
            ->assertJsonPath('item.is_selected', false)
            ->assertJsonPath('cart.subtotal', 330)
            ->assertJsonPath('cart.selected_unique_item_count', 1)
            ->assertJsonPath('cart.unique_item_count', 2)
            ->assertJsonPath('cart.has_selection', true);

        $this->assertFalse($secondItem->refresh()->is_selected);

        $this->actingAs($customer)
            ->get('/cart')
            ->assertOk()
            ->assertSee('₱330.00')
            ->assertSee('2 of 3 items');

        $firstItem = $cart->items()->where('product_id', $product->id)->firstOrFail();

        $this->actingAs($customer)
            ->patchJson(route('cart.items.selection', $firstItem), ['is_selected' => false])
            ->assertOk()
            ->assertJsonPath('cart.subtotal', 0)
            ->assertJsonPath('cart.has_selection', false)
            ->assertJsonPath('cart.selected_unique_item_count', 0);

        $cartResponse = $this->actingAs($customer)->get('/cart')->assertOk();

        $this->assertMatchesRegularExpression('/<button[^>]*data-checkout-button[^>]*disabled/', $cartResponse->getContent());
        $cartResponse->assertSee('Select at least one item to checkout.');

        $this->actingAs($customer)
            ->get('/checkout')
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', 'Select at least one item to checkout.');

        $this->actingAs($customer)
            ->post('/checkout', [
                'service_type' => 'dine_in',
                'people_count' => 1,
            ])
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(2, $cart->items()->count());
    }

    public function test_checkout_only_places_selected_items_and_keeps_unselected_size_lines_in_the_cart(): void
    {
        $customer = $this->customer();
        $product = Product::with(['options', 'addons'])->where('slug', 'iced-americano')->firstOrFail();

        $product->options()
            ->where('group_name', 'size')
            ->where('name', 'Regular')
            ->update(['price' => 40]);
        $product->options()
            ->where('group_name', 'size')
            ->where('name', 'Large')
            ->update(['price' => 75]);

        $product->load('options');

        $optionsForSize = function (string $sizeName) use ($product): array {
            return $product->options
                ->where('is_active', true)
                ->groupBy('group_name')
                ->map(function ($group, string $groupName) use ($sizeName) {
                    $option = $groupName === 'size'
                        ? $group->firstWhere('name', $sizeName)
                        : ($group->firstWhere('is_default', true) ?? $group->first());

                    return $option->id;
                })
                ->values()
                ->all();
        };

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'options' => $optionsForSize('Regular'),
        ])->assertRedirect(route('cart.index'));

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'options' => $optionsForSize('Large'),
        ])->assertRedirect(route('cart.index'));

        $cart = Cart::where('user_id', $customer->id)->firstOrFail();
        $largeItem = $cart->items()->where('unit_price', 75)->firstOrFail();

        $this->actingAs($customer)
            ->patchJson(route('cart.items.selection', $largeItem), ['is_selected' => false])
            ->assertOk()
            ->assertJsonPath('cart.subtotal', 80);

        $this->actingAs($customer)
            ->get('/checkout')
            ->assertOk()
            ->assertSee('2× '.$product->name)
            ->assertSee('₱80.00');

        $this->actingAs($customer)->post('/checkout', [
            'service_type' => 'dine_in',
            'people_count' => 2,
        ])->assertRedirect();

        $order = Order::with('items')->latest('id')->firstOrFail();

        $this->assertSame(80.0, (float) $order->subtotal);
        $this->assertSame(80.0, (float) $order->total);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(40.0, (float) $order->items->first()->unit_price);

        $remainingItem = Cart::where('user_id', $customer->id)->firstOrFail()->items()->firstOrFail();

        $this->assertSame(1, $remainingItem->quantity);
        $this->assertSame(75.0, (float) $remainingItem->unit_price);
        $this->assertFalse($remainingItem->is_selected);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_selected_items_can_be_reset_and_checked_out_after_an_unselected_attempt(): void
    {
        $customer = $this->customer();
        $product = $this->signature();

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'options' => $this->defaultOptions($product),
        ])->assertRedirect(route('cart.index'));

        $item = Cart::where('user_id', $customer->id)->firstOrFail()->items()->firstOrFail();

        $this->actingAs($customer)
            ->patchJson(route('cart.items.selection', $item), ['is_selected' => false])
            ->assertOk()
            ->assertJsonPath('cart.has_selection', false);

        $this->actingAs($customer)
            ->patchJson(route('cart.items.selection', $item), ['is_selected' => true])
            ->assertOk()
            ->assertJsonPath('cart.subtotal', 165)
            ->assertJsonPath('cart.has_selection', true);

        $this->actingAs($customer)->post('/checkout', [
            'service_type' => 'dine_in',
            'people_count' => 1,
        ])->assertRedirect();

        $order = Order::latest('id')->firstOrFail();

        $this->assertSame(165.0, (float) $order->total);
        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('cart_items', 0);
    }

    private function customer(): User
    {
        return User::where('email', 'customer@brewandgo.test')->firstOrFail();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@brewandgo.test')->firstOrFail();
    }

    private function signature(): Product
    {
        return Product::with(['options', 'addons'])->where('slug', 'brew-and-go-signature')->firstOrFail();
    }

    private function defaultOptions(Product $product): array
    {
        return $product->options
            ->where('is_active', true)
            ->groupBy('group_name')
            ->map(function ($group) {
                $option = $group->firstWhere('is_default', true) ?? $group->first();

                return $option->id;
            })
            ->values()
            ->all();
    }

    private function placeBasicOrder(User $customer): Order
    {
        $product = $this->signature();

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'options' => $this->defaultOptions($product),
        ]);

        $this->post('/checkout', [
            'service_type' => 'dine_in',
            'people_count' => 2,
        ]);

        return Order::latest()->firstOrFail();
    }
}

<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Database\Seeder;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'coffee' => Category::updateOrCreate(
                ['slug' => 'coffee'],
                [
                    'name' => 'Coffee',
                    'description' => 'Bold espresso, velvety lattes, and slow-brewed signatures.',
                    'image' => 'images/categories/coffee.jpg',
                    'is_active' => true,
                    'sort_order' => 1,
                ]
            ),
            'non-coffee' => Category::updateOrCreate(
                ['slug' => 'non-coffee'],
                [
                    'name' => 'Non-Coffee',
                    'description' => 'Creamy, fruity, and refreshing alternatives for every mood.',
                    'image' => 'images/categories/non-coffee.jpg',
                    'is_active' => true,
                    'sort_order' => 2,
                ]
            ),
            'food' => Category::updateOrCreate(
                ['slug' => 'food'],
                [
                    'name' => 'Food',
                    'description' => 'Comfort plates built to pair perfectly with your drink.',
                    'image' => 'images/categories/food.jpg',
                    'is_active' => true,
                    'sort_order' => 3,
                ]
            ),
        ];

        $addons = collect([
            ['name' => 'Extra Espresso Shot', 'price' => 30],
            ['name' => 'Oat Milk', 'price' => 35],
            ['name' => 'Almond Milk', 'price' => 35],
            ['name' => 'Extra Caramel', 'price' => 20],
            ['name' => 'Whipped Cream', 'price' => 25],
            ['name' => 'Cheese Slice', 'price' => 20],
            ['name' => 'Bacon Bits', 'price' => 35],
            ['name' => 'Extra Sauce', 'price' => 15],
            ['name' => 'Extra Patty', 'price' => 80],
        ])->mapWithKeys(fn (array $addon) => [
            $addon['name'] => Addon::updateOrCreate(
                ['name' => $addon['name']],
                ['price' => $addon['price'], 'is_active' => true]
            ),
        ]);

        $drinkAddons = ['Extra Espresso Shot', 'Oat Milk', 'Almond Milk', 'Extra Caramel', 'Whipped Cream'];
        $foodAddons = ['Cheese Slice', 'Bacon Bits', 'Extra Sauce', 'Extra Patty'];

        $products = [
            ['coffee', 'Brew & Go Signature', 'brew-and-go-signature', 'Double espresso, dark cocoa, salted caramel, and velvet cream. The house icon.', 165, 'images/products/signature.jpg', true, 'drink', $drinkAddons],
            ['coffee', 'Spanish Latte', 'spanish-latte', 'Rich espresso folded into condensed milk with a caramel finish.', 150, 'images/products/spanish-latte.jpg', true, 'drink', $drinkAddons],
            ['coffee', 'Iced Americano', 'iced-americano', 'Clean, cold, and confident. Two shots over crystal-clear ice.', 120, 'images/products/americano.jpg', false, 'drink', $drinkAddons],
            ['coffee', 'Hazelnut Cappuccino', 'hazelnut-cappuccino', 'A cloud of microfoam with toasted hazelnut and a dusting of cocoa.', 155, 'images/products/cappuccino.jpg', false, 'drink', $drinkAddons],
            ['coffee', 'Mocha Noir', 'mocha-noir', '70% dark chocolate, espresso, and steamed milk. Deep and decadent.', 165, 'images/products/mocha.jpg', false, 'drink', $drinkAddons],
            ['non-coffee', 'Matcha Cloud', 'matcha-cloud', 'Ceremonial matcha, creamy milk, and a soft vanilla cloud.', 170, 'images/products/matcha.jpg', true, 'drink', $drinkAddons],
            ['non-coffee', 'Strawberry Velvet', 'strawberry-velvet', 'Sun-ripened strawberries blended with cream and a hint of vanilla.', 165, 'images/products/strawberry.jpg', true, 'drink', $drinkAddons],
            ['non-coffee', 'Choco Hazelnut Frappe', 'choco-hazelnut-frappe', 'Blended chocolate, roasted hazelnut, and whipped cream.', 175, 'images/products/frappe.jpg', false, 'drink', $drinkAddons],
            ['non-coffee', 'Citrus Iced Tea', 'citrus-iced-tea', 'Bright black tea, calamansi, and orange over crushed ice.', 120, 'images/products/iced-tea.jpg', false, 'drink', $drinkAddons],
            ['food', 'Truffle Mushroom Melt', 'truffle-mushroom-melt', 'Sautéed mushrooms, truffle cream, and molten cheese on toasted sourdough.', 245, 'images/products/mushroom-melt.jpg', true, 'food', $foodAddons],
            ['food', 'Smoked Bacon Burger', 'smoked-bacon-burger', 'Smoked bacon, aged cheddar, pickles, and house sauce on a brioche bun.', 265, 'images/products/burger.jpg', true, 'food', $foodAddons],
            ['food', 'Garlic Parmesan Fries', 'garlic-parmesan-fries', 'Golden fries tossed in garlic butter, parmesan, and parsley.', 155, 'images/products/fries.jpg', false, 'food', $foodAddons],
            ['food', 'Butter Croissant', 'butter-croissant', 'Laminated 24 hours for a shatteringly crisp, buttery bite.', 120, 'images/products/croissant.jpg', false, 'food', $foodAddons],
        ];

        foreach ($products as [$category, $name, $slug, $description, $price, $image, $featured, $optionType, $addonNames]) {
            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $categories[$category]->id,
                    'name' => $name,
                    'description' => $description,
                    'base_price' => $price,
                    'image' => $image,
                    'is_featured' => $featured,
                    'is_active' => true,
                    'stock_quantity' => 50,
                    'low_stock_threshold' => 10,
                    'is_sold_out' => false,
                ]
            );

            $this->seedOptions($product, $optionType);
            $product->addons()->sync(
                collect($addonNames)->map(fn (string $addon) => $addons[$addon]->id)->all()
            );
        }
    }

    private function seedOptions(Product $product, string $type): void
    {
        $groups = [
            'size' => [['Regular', 0, true], ['Large', 30, false]],
            'sweetness' => [['0% Sugar', 0, false], ['50% Sugar', 0, false], ['100% Sugar', 0, true]],
            'ice' => [['Hot', 0, false], ['Light Ice', 0, false], ['Regular Ice', 0, true], ['Extra Ice', 0, false]],
            'flavor' => [['Classic', 0, true], ['Vanilla', 20, false], ['Caramel', 20, false], ['Hazelnut', 20, false]],
        ];

        if ($type === 'food') {
            $groups['size'] = [['Regular', 0, true], ['Sharing', 80, false]];
            unset($groups['sweetness'], $groups['ice']);
            $groups['flavor'] = [['Classic', 0, true]];
        }

        foreach ($groups as $group => $options) {
            foreach ($options as $index => [$name, $adjustment, $isDefault]) {
                ProductOption::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'group_name' => $group,
                        'name' => $name,
                    ],
                    [
                        'price_adjustment' => $adjustment,
                        'price' => $group === 'size' ? round((float) $product->base_price + $adjustment, 2) : null,
                        'is_default' => $isDefault,
                        'is_active' => true,
                        'sort_order' => $index + 1,
                    ]
                );
            }
        }
    }
}

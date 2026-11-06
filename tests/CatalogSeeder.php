<?php

namespace Tests;

use App\Models\Addon;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
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

        $drinkAddons = [
            'Extra Espresso Shot',
            'Oat Milk',
            'Almond Milk',
            'Extra Caramel',
            'Whipped Cream',
        ];

        $foodAddons = [
            'Cheese Slice',
            'Bacon Bits',
            'Extra Sauce',
            'Extra Patty',
        ];

        $products = [
            [
                'category' => 'coffee',
                'name' => 'Brew & Go Signature',
                'slug' => 'brew-and-go-signature',
                'description' => 'Double espresso, dark cocoa, salted caramel, and velvet cream. The house icon.',
                'base_price' => 165,
                'image' => 'images/products/signature.jpg',
                'is_featured' => true,
                'options' => 'drink',
                'addons' => $drinkAddons,
            ],
            [
                'category' => 'coffee',
                'name' => 'Spanish Latte',
                'slug' => 'spanish-latte',
                'description' => 'Rich espresso folded into condensed milk with a caramel finish.',
                'base_price' => 150,
                'image' => 'images/products/spanish-latte.jpg',
                'is_featured' => true,
                'options' => 'drink',
                'addons' => $drinkAddons,
            ],
            [
                'category' => 'coffee',
                'name' => 'Iced Americano',
                'slug' => 'iced-americano',
                'description' => 'Clean, cold, and confident. Two shots over crystal-clear ice.',
                'base_price' => 120,
                'image' => 'images/products/americano.jpg',
                'is_featured' => false,
                'options' => 'drink',
                'addons' => $drinkAddons,
            ],
            [
                'category' => 'coffee',
                'name' => 'Hazelnut Cappuccino',
                'slug' => 'hazelnut-cappuccino',
                'description' => 'A cloud of microfoam with toasted hazelnut and a dusting of cocoa.',
                'base_price' => 155,
                'image' => 'images/products/cappuccino.jpg',
                'is_featured' => false,
                'options' => 'drink',
                'addons' => $drinkAddons,
            ],
            [
                'category' => 'coffee',
                'name' => 'Mocha Noir',
                'slug' => 'mocha-noir',
                'description' => '70% dark chocolate, espresso, and steamed milk. Deep and decadent.',
                'base_price' => 165,
                'image' => 'images/products/mocha.jpg',
                'is_featured' => false,
                'options' => 'drink',
                'addons' => $drinkAddons,
            ],
            [
                'category' => 'non-coffee',
                'name' => 'Matcha Cloud',
                'slug' => 'matcha-cloud',
                'description' => 'Ceremonial matcha, creamy milk, and a soft vanilla cloud.',
                'base_price' => 170,
                'image' => 'images/products/matcha.jpg',
                'is_featured' => true,
                'options' => 'drink',
                'addons' => $drinkAddons,
            ],
            [
                'category' => 'non-coffee',
                'name' => 'Strawberry Velvet',
                'slug' => 'strawberry-velvet',
                'description' => 'Sun-ripened strawberries blended with cream and a hint of vanilla.',
                'base_price' => 165,
                'image' => 'images/products/strawberry.jpg',
                'is_featured' => true,
                'options' => 'drink',
                'addons' => $drinkAddons,
            ],
            [
                'category' => 'non-coffee',
                'name' => 'Choco Hazelnut Frappe',
                'slug' => 'choco-hazelnut-frappe',
                'description' => 'Blended chocolate, roasted hazelnut, and whipped cream.',
                'base_price' => 175,
                'image' => 'images/products/frappe.jpg',
                'is_featured' => false,
                'options' => 'drink',
                'addons' => $drinkAddons,
            ],
            [
                'category' => 'non-coffee',
                'name' => 'Citrus Iced Tea',
                'slug' => 'citrus-iced-tea',
                'description' => 'Bright black tea, calamansi, and orange over crushed ice.',
                'base_price' => 120,
                'image' => 'images/products/iced-tea.jpg',
                'is_featured' => false,
                'options' => 'drink',
                'addons' => $drinkAddons,
            ],
            [
                'category' => 'food',
                'name' => 'Truffle Mushroom Melt',
                'slug' => 'truffle-mushroom-melt',
                'description' => 'Sautéed mushrooms, truffle cream, and molten cheese on toasted sourdough.',
                'base_price' => 245,
                'image' => 'images/products/mushroom-melt.jpg',
                'is_featured' => true,
                'options' => 'food',
                'addons' => $foodAddons,
            ],
            [
                'category' => 'food',
                'name' => 'Smoked Bacon Burger',
                'slug' => 'smoked-bacon-burger',
                'description' => 'Smoked bacon, aged cheddar, pickles, and house sauce on a brioche bun.',
                'base_price' => 265,
                'image' => 'images/products/burger.jpg',
                'is_featured' => true,
                'options' => 'food',
                'addons' => $foodAddons,
            ],
            [
                'category' => 'food',
                'name' => 'Garlic Parmesan Fries',
                'slug' => 'garlic-parmesan-fries',
                'description' => 'Golden fries tossed in garlic butter, parmesan, and parsley.',
                'base_price' => 155,
                'image' => 'images/products/fries.jpg',
                'is_featured' => false,
                'options' => 'food',
                'addons' => $foodAddons,
            ],
            [
                'category' => 'food',
                'name' => 'Butter Croissant',
                'slug' => 'butter-croissant',
                'description' => 'Laminated 24 hours for a shatteringly crisp, buttery bite.',
                'base_price' => 120,
                'image' => 'images/products/croissant.jpg',
                'is_featured' => false,
                'options' => 'food',
                'addons' => $foodAddons,
            ],
        ];

        foreach ($products as $data) {
            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'category_id' => $categories[$data['category']]->id,
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'base_price' => $data['base_price'],
                    'image' => $data['image'],
                    'is_featured' => $data['is_featured'],
                    'is_active' => true,
                ]
            );

            $this->seedOptions($product, $data['options']);
            $product->addons()->sync(
                collect($data['addons'])->map(fn (string $name) => $addons[$name]->id)->all()
            );
        }
    }

    private function seedOptions(Product $product, string $type): void
    {
        $groups = [
            'size' => [
                ['Regular', 0, true],
                ['Large', 30, false],
            ],
            'sweetness' => [
                ['0% Sugar', 0, false],
                ['50% Sugar', 0, false],
                ['100% Sugar', 0, true],
            ],
            'ice' => [
                ['Hot', 0, false],
                ['Light Ice', 0, false],
                ['Regular Ice', 0, true],
                ['Extra Ice', 0, false],
            ],
            'flavor' => [
                ['Classic', 0, true],
                ['Vanilla', 20, false],
                ['Caramel', 20, false],
                ['Hazelnut', 20, false],
            ],
        ];

        if ($type === 'food') {
            $groups['size'] = [
                ['Regular', 0, true],
                ['Sharing', 80, false],
            ];
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
                        'price' => $group === 'size'
                            ? round((float) $product->base_price + $adjustment, 2)
                            : null,
                        'is_default' => $isDefault,
                        'is_active' => true,
                        'sort_order' => $index + 1,
                    ]
                );
            }
        }
    }
}

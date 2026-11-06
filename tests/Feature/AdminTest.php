<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Addon;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderRequest;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Promotion;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\PaymentQrService;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seedTestCatalog();
        $this->createCustomer();
    }

    public function test_guests_are_redirected_and_customers_are_forbidden_from_admin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));

        $this->actingAs($this->customer())
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($this->customer())
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }

    public function test_admin_can_log_in_and_log_out_through_the_admin_console(): void
    {
        $this->post(route('admin.login.store'), [
            'email' => 'admin@brewandgo.test',
            'password' => 'Admin@12345',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($this->admin());

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Brew & Go Admin');

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_admin_sidebar_renders_a_collapse_toggle(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-admin-sidebar-toggle', false)
            ->assertSee('aria-controls="adminSidebar"', false)
            ->assertSee('id="adminSidebar"', false);
    }

    public function test_admin_order_search_returns_only_matching_records(): void
    {
        $customer = $this->customer();
        $matching = $this->makeOrder($customer, OrderStatus::Pending);
        $other = $this->makeOrder($customer, OrderStatus::Pending);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['search' => $matching->order_number]))
            ->assertOk()
            ->assertSee($matching->order_number)
            ->assertDontSee($other->order_number);
    }

    public function test_non_admin_cannot_log_in_through_the_admin_console(): void
    {
        $this->post(route('admin.login.store'), [
            'email' => 'customer@brewandgo.test',
            'password' => 'Customer@12345',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_create_update_and_delete_a_category(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Seasonal Drinks',
            'description' => 'Limited-time creations.',
            'is_active' => 1,
            'sort_order' => 4,
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::where('slug', 'seasonal-drinks')->firstOrFail();
        $this->assertTrue($category->is_active);

        $this->actingAs($admin)->patch(route('admin.categories.update', $category), [
            'name' => 'Seasonal Specials',
            'description' => 'Updated description.',
            'is_active' => 0,
            'sort_order' => 5,
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Seasonal Specials',
            'slug' => 'seasonal-specials',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_changes_from_the_admin_are_reflected_on_the_customer_menu(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Seasonal Refreshers',
            'description' => 'Limited-time creations.',
            'is_active' => 1,
            'sort_order' => 9,
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::where('slug', 'seasonal-refreshers')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Mango Cold Brew',
            'slug' => '',
            'description' => 'A seasonal refresher.',
            'size_prices' => [
                'small' => 129,
                'medium' => 149,
                'large' => 169,
            ],
            'is_active' => 1,
        ])->assertRedirect();

        $product = Product::where('slug', 'mango-cold-brew')->firstOrFail();

        $menu = $this->actingAs($customer)->get(route('menu.index'))
            ->assertOk()
            ->assertSee('Seasonal Refreshers')
            ->assertSee('data-menu-category="seasonal-refreshers"', false)
            ->assertSee($product->name)
            ->assertSee('data-product-category="seasonal-refreshers"', false);

        $this->assertSame(1, substr_count($menu->getContent(), 'data-menu-category="seasonal-refreshers"'));

        $this->actingAs($admin)->patch(route('admin.categories.update', $category), [
            'name' => 'Holiday Refreshers',
            'description' => $category->description,
            'is_active' => 1,
            'sort_order' => 9,
        ])->assertRedirect(route('admin.categories.index'));

        $category->refresh();

        $this->assertSame('holiday-refreshers', $category->slug);
        $this->assertSame($category->id, $product->refresh()->category_id);

        $menu = $this->actingAs($customer)->get(route('menu.index'))
            ->assertOk()
            ->assertSee('Holiday Refreshers')
            ->assertSee('data-menu-category="holiday-refreshers"', false)
            ->assertSee($product->name)
            ->assertSee('data-product-category="holiday-refreshers"', false)
            ->assertDontSee('data-menu-category="seasonal-refreshers"', false);

        $this->assertSame(1, substr_count($menu->getContent(), 'data-menu-category="holiday-refreshers"'));

        $this->actingAs($admin)->patch(route('admin.categories.update', $category), [
            'name' => 'Holiday Refreshers',
            'description' => $category->description,
            'is_active' => 0,
            'sort_order' => 9,
        ])->assertRedirect(route('admin.categories.index'));

        $this->actingAs($customer)->get(route('menu.index'))
            ->assertOk()
            ->assertDontSee('data-menu-category="holiday-refreshers"', false)
            ->assertDontSee($product->name);

        $this->actingAs($admin)->patch(route('admin.categories.update', $category), [
            'name' => 'Holiday Refreshers',
            'description' => $category->description,
            'is_active' => 1,
            'sort_order' => 9,
        ])->assertRedirect(route('admin.categories.index'));

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->actingAs($customer)->get(route('menu.index'))
            ->assertOk()
            ->assertDontSee('Holiday Refreshers')
            ->assertDontSee('data-menu-category="holiday-refreshers"', false);
    }

    public function test_admin_can_create_a_product_with_addons_and_drink_sizes(): void
    {
        $admin = $this->admin();
        $category = Category::where('slug', 'coffee')->firstOrFail();
        $addon = Addon::where('name', 'Oat Milk')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Golden Test Latte',
            'slug' => '',
            'description' => 'A test-only latte.',
            'size_prices' => [
                'small' => 160,
                'medium' => 180,
                'large' => 200,
            ],
            'is_featured' => 1,
            'is_active' => 1,
            'addons' => [$addon->id],
        ])->assertRedirect();

        $product = Product::where('slug', 'golden-test-latte')->firstOrFail();

        $this->assertSame($category->id, $product->category_id);
        $this->assertSame(180.0, (float) $product->base_price);
        $this->assertTrue($product->is_featured);
        $this->assertTrue($product->is_active);
        $this->assertTrue($product->addons->contains($addon));

        $sizes = $product->options()->where('group_name', 'size')->orderBy('sort_order')->get()->keyBy('name');

        $this->assertCount(3, $sizes);
        $this->assertSame(160.0, (float) $sizes['Small']->price);
        $this->assertSame(180.0, (float) $sizes['Medium']->price);
        $this->assertSame(200.0, (float) $sizes['Large']->price);
        $this->assertTrue($sizes->every(fn (ProductOption $option) => ! $option->is_default && $option->is_active));
    }

    public function test_admin_can_update_size_prices_from_the_product_form_and_the_customer_menu_reflects_them(): void
    {
        $admin = $this->admin();
        $category = Category::where('slug', 'coffee')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Size Price Test Latte',
            'slug' => '',
            'description' => 'A drink used to test size prices.',
            'size_prices' => [
                'small' => 40,
                'medium' => 55,
                'large' => 75,
            ],
            'is_active' => 1,
        ])->assertRedirect();

        $product = Product::where('slug', 'size-price-test-latte')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Drink sizes')
            ->assertSee('name="size_prices[small]"', false)
            ->assertSee('name="size_prices[medium]"', false)
            ->assertSee('name="size_prices[large]"', false)
            ->assertSee('value="40.00"', false)
            ->assertSee('value="55.00"', false)
            ->assertSee('value="75.00"', false)
            ->assertDontSee('name="base_price"', false)
            ->assertDontSee('Base price')
            ->assertDontSee('Customization options');

        $product->options()->create([
            'group_name' => 'size',
            'name' => 'Regular',
            'price' => 60,
            'price_adjustment' => 0,
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'size_prices' => [
                'small' => 45,
                'medium' => 60,
                'large' => 80,
            ],
            'is_active' => 1,
        ])->assertRedirect(route('admin.products.edit', $product));

        $sizes = $product->fresh()->options()->where('group_name', 'size')->get()->keyBy('name');

        $this->assertCount(4, $sizes, 'The legacy size row is kept for order history but hidden from customers.');
        $this->assertSame(45.0, (float) $sizes['Small']->price);
        $this->assertSame(60.0, (float) $sizes['Medium']->price);
        $this->assertSame(80.0, (float) $sizes['Large']->price);
        $this->assertFalse($sizes['Small']->is_default);
        $this->assertFalse($sizes['Medium']->is_default);
        $this->assertFalse($sizes['Large']->is_default);
        $this->assertFalse($sizes['Regular']->is_active);
        $this->assertSame(60.0, (float) $product->fresh()->base_price, 'The base price stays in sync with the Medium size price.');

        $this->actingAs($admin)
            ->get(route('menu.show', $product))
            ->assertOk()
            ->assertSeeInOrder(['Small', '₱45.00', 'Medium', '₱60.00', 'Large', '₱80.00'])
            ->assertDontSee('Regular')
            ->assertDontSee('From')
            ->assertDontSee('depending on size')
            ->assertSee('Select a size to continue.');
    }

    public function test_product_size_prices_are_required_and_validated(): void
    {
        $admin = $this->admin();
        $category = Category::where('slug', 'coffee')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'Invalid Size Product',
                'slug' => 'invalid-size-product',
                'description' => 'A product with invalid size prices.',
                'size_prices' => [
                    'small' => -1,
                    'medium' => 'not-a-price',
                    'large' => '',
                ],
                'is_active' => 1,
            ])
            ->assertSessionHasErrors([
                'size_prices.small',
                'size_prices.medium',
                'size_prices.large',
            ]);

        $this->assertDatabaseMissing('products', ['slug' => 'invalid-size-product']);
    }

    public function test_size_price_updates_reject_size_options_from_another_product(): void
    {
        $admin = $this->admin();
        $product = Product::where('slug', 'iced-americano')->firstOrFail();
        $foreignSize = Product::where('slug', 'spanish-latte')->firstOrFail()
            ->options()
            ->where('group_name', 'size')
            ->firstOrFail();

        $this->actingAs($admin)->put(route('admin.products.size-prices.update', $product), [
            'sizes' => [
                ['id' => $foreignSize->id, 'name' => 'Tampered', 'price' => 10, 'is_active' => 1],
            ],
            'default_size' => 0,
        ])->assertNotFound();

        $foreignSize->refresh();

        $this->assertSame('Regular', $foreignSize->name);
        $this->assertSame(150.0, (float) $foreignSize->price);
    }

    public function test_size_price_updates_validate_prices_names_and_default_choice(): void
    {
        $admin = $this->admin();
        $product = Product::where('slug', 'iced-americano')->firstOrFail();
        $sizes = $product->options()->where('group_name', 'size')->orderBy('sort_order')->get();

        $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->put(route('admin.products.size-prices.update', $product), [
                'sizes' => [
                    ['id' => $sizes[0]->id, 'name' => 'Regular', 'price' => -1, 'is_active' => 1],
                    ['id' => $sizes[1]->id, 'name' => 'Regular', 'price' => 75, 'is_active' => 1],
                ],
                'default_size' => 5,
            ])
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors(['sizes.0.price', 'sizes.1.name', 'default_size']);

        $this->assertSame(120.0, (float) $sizes[0]->fresh()->price);
        $this->assertSame(150.0, (float) $sizes[1]->fresh()->price);
        $this->assertTrue($sizes[0]->fresh()->is_default);
    }

    public function test_resaving_the_current_default_size_keeps_it_selected(): void
    {
        $admin = $this->admin();
        $product = Product::where('slug', 'iced-americano')->firstOrFail();
        $sizes = $product->options()->where('group_name', 'size')->orderBy('sort_order')->get()->keyBy('name');

        $payload = [
            'sizes' => [
                ['id' => $sizes['Regular']->id, 'name' => 'Regular', 'price' => 120, 'is_active' => 1],
                ['id' => $sizes['Large']->id, 'name' => 'Large', 'price' => 150, 'is_active' => 1],
            ],
            'default_size' => 0,
        ];

        foreach ([1, 2] as $attempt) {
            $this->actingAs($admin)
                ->put(route('admin.products.size-prices.update', $product), $payload)
                ->assertRedirect(route('admin.products.edit', $product));

            $this->assertTrue((bool) $sizes['Regular']->fresh()->is_default, "Regular should stay default after save {$attempt}");
            $this->assertFalse((bool) $sizes['Large']->fresh()->is_default, "Large should stay non-default after save {$attempt}");
        }
    }

    public function test_admin_dashboard_shows_operational_overview_and_management_navigation(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee("Today's revenue", false)
            ->assertSee("Today's orders", false)
            ->assertSee('Active orders')
            ->assertSee('Order pipeline')
            ->assertSee('Recent orders')
            ->assertDontSee('Size pricing coverage')
            ->assertDontSee('Size & pricing')
            ->assertSeeInOrder([
                'Dashboard',
                'Products',
                'Categories',
                'Orders',
                'Customers',
                'Settings',
            ])
            ->assertSee('href="'.route('admin.settings.edit').'"', false);
    }

    public function test_admin_can_update_storefront_settings_and_account_credentials(): void
    {
        $admin = $this->admin();

        $settings = $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Storefront details')
            ->assertSee('Admin account')
            ->assertSee($admin->email);

        $this->assertSame(3, substr_count($settings->getContent(), 'data-password-toggle'));

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'brand_name' => 'Golden Brew Co.',
            'brand_tagline' => 'Small batch, big moments',
            'contact_phone' => '+63 900 000 0000',
            'contact_email' => 'hello@goldenbrew.test',
            'name' => 'Operations Admin',
            'email' => 'ops@goldenbrew.test',
            'current_password' => 'Admin@12345',
            'password' => 'NewAdmin@12345',
            'password_confirmation' => 'NewAdmin@12345',
        ])->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('site_settings', [
            'key' => 'brand_name',
            'value' => 'Golden Brew Co.',
        ]);
        $this->assertDatabaseHas('site_settings', [
            'key' => 'contact_email',
            'value' => 'hello@goldenbrew.test',
        ]);

        $admin->refresh();

        $this->assertSame('Operations Admin', $admin->name);
        $this->assertSame('ops@goldenbrew.test', $admin->email);
        $this->assertTrue(Hash::check('NewAdmin@12345', $admin->password));
    }

    public function test_admin_can_upload_replace_and_remove_the_payment_qr_code(): void
    {
        Storage::fake(PaymentQrService::DISK);

        $admin = $this->admin();
        $payload = [
            'brand_name' => 'Brew & Go',
            'name' => $admin->name,
            'email' => $admin->email,
            'payment_qr_instructions' => 'Scan with GCash, then upload your receipt.',
        ];

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            ...$payload,
            'payment_qr_code' => $this->fakePng('first-qr.png'),
        ])->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasNoErrors();

        $first = SiteSetting::where('key', 'payment_qr_code')->value('value');

        $this->assertNotNull($first);
        $this->assertSame('Scan with GCash, then upload your receipt.', SiteSetting::where('key', 'payment_qr_instructions')->value('value'));
        Storage::disk(PaymentQrService::DISK)->assertExists(basename($first));

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            ...$payload,
            'payment_qr_code' => $this->fakePng('second-qr.png'),
        ])->assertSessionHasNoErrors();

        $second = SiteSetting::where('key', 'payment_qr_code')->value('value');

        $this->assertNotSame($first, $second);
        Storage::disk(PaymentQrService::DISK)->assertMissing(basename($first));
        Storage::disk(PaymentQrService::DISK)->assertExists(basename($second));

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            ...$payload,
            'remove_payment_qr_code' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame('', SiteSetting::where('key', 'payment_qr_code')->value('value'));
        Storage::disk(PaymentQrService::DISK)->assertMissing(basename($second));
    }

    public function test_settings_changes_require_the_current_password(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), [
                'brand_name' => 'Brew & Go',
                'name' => $admin->name,
                'email' => $admin->email,
                'current_password' => 'wrong-password',
                'password' => 'NewAdmin@12345',
                'password_confirmation' => 'NewAdmin@12345',
            ])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('Admin@12345', $admin->fresh()->password));
    }

    public function test_admin_can_upload_replace_and_remove_a_product_picture(): void
    {
        Storage::fake(ProductImageService::DISK);

        $admin = $this->admin();
        $category = Category::where('slug', 'coffee')->firstOrFail();
        $payload = [
            'category_id' => $category->id,
            'name' => 'Picture Test Latte',
            'slug' => 'picture-test-latte',
            'description' => 'A latte used to test product pictures.',
            'size_prices' => [
                'small' => 180,
                'medium' => 200,
                'large' => 220,
            ],
            'is_featured' => 0,
            'is_active' => 1,
        ];

        $this->actingAs($admin)->post(route('admin.products.store'), [
            ...$payload,
            'image_file' => $this->fakePng('first-picture.png'),
        ])->assertRedirect();

        $product = Product::where('slug', 'picture-test-latte')->firstOrFail();
        $firstImage = basename($product->image);

        $this->assertStringStartsWith(ProductImageService::DIRECTORY.'/', $product->image);
        Storage::disk(ProductImageService::DISK)->assertExists($firstImage);

        $this->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="image_file"', false)
            ->assertSee('Remove current picture');

        $this->get('/menu')
            ->assertOk()
            ->assertSee('Picture Test Latte')
            ->assertSee(asset($product->image));

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            ...$payload,
            'name' => 'Picture Test Latte Updated',
            'image_file' => $this->fakePng('second-picture.png'),
        ])->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();
        $secondImage = basename($product->image);

        $this->assertNotSame($firstImage, $secondImage);
        $this->assertSame('Picture Test Latte Updated', $product->name);
        $this->assertSame(200.0, (float) $product->base_price);
        Storage::disk(ProductImageService::DISK)->assertMissing($firstImage);
        Storage::disk(ProductImageService::DISK)->assertExists($secondImage);

        $this->get('/menu')
            ->assertOk()
            ->assertSee('Picture Test Latte Updated')
            ->assertSee(asset($product->image));

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            ...$payload,
            'name' => 'Picture Test Latte Updated',
            'remove_image' => 1,
        ])->assertRedirect(route('admin.products.edit', $product));

        $this->assertNull($product->refresh()->image);
        Storage::disk(ProductImageService::DISK)->assertMissing($secondImage);
    }

    public function test_deleting_a_product_removes_its_uploaded_picture(): void
    {
        Storage::fake(ProductImageService::DISK);

        $admin = $this->admin();
        $category = Category::where('slug', 'coffee')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Delete Picture Product',
            'slug' => 'delete-picture-product',
            'description' => 'A product used to test picture cleanup.',
            'size_prices' => [
                'small' => 130,
                'medium' => 150,
                'large' => 170,
            ],
            'is_active' => 1,
            'image_file' => $this->fakePng('delete-picture.png'),
        ])->assertRedirect();

        $product = Product::where('slug', 'delete-picture-product')->firstOrFail();
        $image = basename($product->image);

        Storage::disk(ProductImageService::DISK)->assertExists($image);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk(ProductImageService::DISK)->assertMissing($image);
    }

    public function test_product_picture_uploads_are_validated_and_admin_only(): void
    {
        Storage::fake(ProductImageService::DISK);

        $admin = $this->admin();
        $category = Category::where('slug', 'coffee')->firstOrFail();
        $payload = [
            'category_id' => $category->id,
            'name' => 'Blocked Picture Product',
            'slug' => 'blocked-picture-product',
            'description' => 'Should never be stored.',
            'size_prices' => [
                'small' => 130,
                'medium' => 150,
                'large' => 170,
            ],
            'is_active' => 1,
        ];

        $this->actingAs($admin)->post(route('admin.products.store'), [
            ...$payload,
            'image_file' => UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('image_file');

        $this->assertDatabaseMissing('products', ['slug' => 'blocked-picture-product']);

        $customer = $this->customer();
        $product = Product::where('slug', 'brew-and-go-signature')->firstOrFail();
        $originalName = $product->name;

        $this->actingAs($customer)->post(route('admin.products.store'), [
            ...$payload,
            'image_file' => $this->fakePng('blocked-picture.png'),
        ])->assertForbidden();

        $this->actingAs($customer)->put(route('admin.products.update', $product), [
            ...$payload,
            'name' => 'Changed By Customer',
        ])->assertForbidden();

        $this->actingAs($customer)
            ->delete(route('admin.products.destroy', $product))
            ->assertForbidden();

        $this->assertDatabaseMissing('products', ['slug' => 'blocked-picture-product']);
        $this->assertSame($originalName, $product->refresh()->name);
        $this->assertSame([], Storage::disk(ProductImageService::DISK)->allFiles());
    }

    public function test_admin_can_transition_an_order_but_cannot_skip_statuses(): void
    {
        $admin = $this->admin();
        $order = $this->makeOrder($this->customer(), OrderStatus::Pending);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'status' => 'Confirmed',
            'note' => 'Order confirmed by staff.',
        ])->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame(OrderStatus::Confirmed, $order->refresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'Confirmed',
            'changed_by' => $admin->id,
        ]);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'status' => 'Completed',
        ])->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Confirmed, $order->refresh()->status);
    }

    public function test_admin_reviewing_a_request_requires_a_note_when_rejecting(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->makeOrder($customer, OrderStatus::Pending);

        $request = OrderRequest::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderRequest::TYPE_CANCELLATION,
            'reason' => 'Please cancel this order.',
            'status' => OrderRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)->patch(route('admin.requests.review', $request), [
            'decision' => OrderRequest::STATUS_REJECTED,
        ])->assertSessionHasErrors('admin_note');

        $this->assertSame(OrderRequest::STATUS_PENDING, $request->refresh()->status);

        $this->actingAs($admin)->patch(route('admin.requests.review', $request), [
            'decision' => OrderRequest::STATUS_REJECTED,
            'admin_note' => 'Preparation has already started.',
        ])->assertRedirect(route('admin.requests.index'));

        $this->assertSame(OrderRequest::STATUS_REJECTED, $request->refresh()->status);
        $this->assertSame($admin->id, $request->reviewed_by);
        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
    }

    public function test_admin_pages_do_not_show_customer_navigation(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertDontSee('>Home<', false)
            ->assertDontSee('>Menu<', false)
            ->assertDontSee('My Cart')
            ->assertDontSee('href="'.route('home').'"', false)
            ->assertDontSee('href="'.route('menu.index').'"', false)
            ->assertDontSee('href="'.route('cart.index').'"', false);

        $customer = $this->customer();
        $category = Category::where('slug', 'coffee')->firstOrFail();
        $product = Product::where('slug', 'brew-and-go-signature')->firstOrFail();
        $addon = Addon::where('name', 'Oat Milk')->firstOrFail();
        $promotion = Promotion::where('code', 'GOLDEN10')->firstOrFail();
        $order = $this->makeOrder($customer, OrderStatus::Pending);

        $this->actingAs($this->admin());

        $adminUrls = [
            route('admin.dashboard'),
            route('admin.categories.index'),
            route('admin.categories.create'),
            route('admin.categories.edit', $category),
            route('admin.products.index'),
            route('admin.products.create'),
            route('admin.products.edit', $product),
            route('admin.addons.index'),
            route('admin.addons.create'),
            route('admin.addons.edit', $addon),
            route('admin.promotions.index'),
            route('admin.promotions.create'),
            route('admin.promotions.edit', $promotion),
            route('admin.carousel.index'),
            route('admin.orders.index'),
            route('admin.orders.show', $order),
            route('admin.requests.index'),
            route('admin.reviews.index'),
            route('admin.customers.index'),
            route('admin.customers.show', $customer),
            route('admin.settings.edit'),
            route('admin.audit.index'),
        ];

        foreach ($adminUrls as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('>Home<', false)
                ->assertDontSee('>Menu<', false)
                ->assertDontSee('My Cart')
                ->assertDontSee('View storefront')
                ->assertDontSee('href="'.route('home').'"', false)
                ->assertDontSee('href="'.route('menu.index').'"', false)
                ->assertDontSee('href="'.route('cart.index').'"', false);
        }
    }

    public function test_customers_cannot_mutate_catalog_through_admin_routes(): void
    {
        $customer = $this->customer();
        $category = Category::where('slug', 'coffee')->firstOrFail();
        $product = Product::where('slug', 'brew-and-go-signature')->firstOrFail();
        $addon = Addon::where('name', 'Oat Milk')->firstOrFail();
        $promotion = Promotion::where('code', 'GOLDEN10')->firstOrFail();

        $this->actingAs($customer)->post(route('admin.categories.store'), [
            'name' => 'Blocked Category',
            'is_active' => 1,
            'sort_order' => 9,
        ])->assertForbidden();

        $this->actingAs($customer)->patch(route('admin.categories.update', $category), [
            'name' => 'Blocked Category Update',
            'is_active' => 1,
            'sort_order' => 1,
        ])->assertForbidden();

        $this->actingAs($customer)->delete(route('admin.categories.destroy', $category))->assertForbidden();

        $this->actingAs($customer)->post(route('admin.addons.store'), [
            'name' => 'Blocked Add-on',
            'price' => 99,
            'is_active' => 1,
        ])->assertForbidden();

        $this->actingAs($customer)->patch(route('admin.promotions.update', $promotion), [
            'title' => 'Blocked Promotion',
            'discount_type' => 'percent',
            'discount_value' => 50,
            'minimum_spend' => 0,
            'is_active' => 1,
        ])->assertForbidden();

        $this->actingAs($customer)->post(route('admin.products.options.store', $product), [
            'group_name' => 'size',
            'name' => 'Blocked Size',
            'price_adjustment' => 10,
            'is_default' => 0,
            'is_active' => 1,
            'sort_order' => 1,
        ])->assertForbidden();

        $this->actingAs($customer)->put(route('admin.products.size-prices.update', $product), [
            'sizes' => [
                ['name' => 'Blocked Size', 'price' => 10, 'is_active' => 1],
            ],
            'default_size' => 0,
        ])->assertForbidden();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Coffee',
        ]);
        $this->assertDatabaseHas('addons', [
            'id' => $addon->id,
            'name' => 'Oat Milk',
        ]);
        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'title' => 'Golden Hour',
        ]);
        $this->assertDatabaseMissing('categories', ['name' => 'Blocked Category']);
        $this->assertDatabaseMissing('addons', ['name' => 'Blocked Add-on']);
        $this->assertDatabaseMissing('product_options', ['name' => 'Blocked Size']);
    }

    public function test_admin_management_pages_render(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $category = Category::where('slug', 'coffee')->firstOrFail();
        $product = Product::where('slug', 'brew-and-go-signature')->firstOrFail();
        $addon = Addon::where('name', 'Oat Milk')->firstOrFail();
        $promotion = Promotion::where('code', 'GOLDEN10')->firstOrFail();
        $order = $this->makeOrder($customer, OrderStatus::Pending);

        $this->actingAs($admin);

        $this->get(route('admin.categories.index'))->assertOk();
        $this->get(route('admin.categories.create'))->assertOk();
        $this->get(route('admin.categories.edit', $category))->assertOk();
        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertDontSee('View on storefront')
            ->assertDontSee('>Filter</button>', false);
        $this->get(route('admin.products.create'))->assertOk();
        $this->get(route('admin.products.edit', $product))->assertOk()->assertDontSee('View on storefront');
        $this->get(route('admin.addons.index'))->assertOk();
        $this->get(route('admin.addons.create'))->assertOk();
        $this->get(route('admin.addons.edit', $addon))->assertOk();
        $this->get(route('admin.promotions.index'))->assertOk();
        $this->get(route('admin.promotions.create'))->assertOk();
        $this->get(route('admin.promotions.edit', $promotion))->assertOk();
        $this->get(route('admin.carousel.index'))
            ->assertOk()
            ->assertSee('Carousel')
            ->assertDontSee('name="title"', false)
            ->assertDontSee('name="image_file"', false);
        $this->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertDontSee('>Filter</button>', false);
        $this->get(route('admin.orders.show', $order))->assertOk();
        $this->get(route('admin.requests.index'))->assertOk()->assertDontSee('>Filter</button>', false);
        $this->get(route('admin.customers.index'))->assertOk()->assertDontSee('>Filter</button>', false);
        $this->get(route('admin.customers.show', $customer))->assertOk();
        $this->get(route('admin.refunds.index'))->assertOk()->assertDontSee('>Filter</button>', false);
        $this->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSee('Audit Logs')
            ->assertDontSee('>Filter</button>', false);
    }

    public function test_admin_can_deactivate_and_reactivate_a_customer(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)
            ->patch(route('admin.customers.toggle', $customer))
            ->assertRedirect();

        $this->assertFalse($customer->refresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.customers.toggle', $customer))
            ->assertRedirect();

        $this->assertTrue($customer->refresh()->is_active);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@brewandgo.test')->firstOrFail();
    }

    private function customer(): User
    {
        return User::where('email', 'customer@brewandgo.test')->firstOrFail();
    }

    private function fakePng(string $name): UploadedFile
    {
        $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZlS8AAAAASUVORK5CYII=', true);

        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    private function makeOrder(User $customer, OrderStatus $status): Order
    {
        return Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $customer->id,
            'service_type' => Order::SERVICE_DINE_IN,
            'people_count' => 2,
            'subtotal' => 200,
            'discount' => 0,
            'total' => 200,
            'status' => $status,
        ]);
    }
}

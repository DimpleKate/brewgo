<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seedTestCatalog();
        $this->createCustomer();
    }

    public function test_public_pages_render(): void
    {
        $product = Product::where('slug', 'brew-and-go-signature')->firstOrFail();

        $this->get('/')->assertOk()->assertViewIs('home')->assertSee('BREW & GO');
        $this->get('/menu')->assertRedirect(route('login'));
        $this->get('/menu/'.$product->slug)->assertRedirect(route('login'));
        $this->get('/about')->assertOk()->assertViewIs('pages.about')->assertSee('About Us');
        $this->get('/contact')
            ->assertOk()
            ->assertViewIs('pages.contact')
            ->assertSee('Contact')
            ->assertSee('Zone 1, Bangued, Abra')
            ->assertSee('+63 995 524 3015')
            ->assertSee('kbbersalona@gmail.com')
            ->assertSee('Monday – Sunday')
            ->assertSee('8:00 AM – 8:00 PM');
        $this->get('/login')->assertOk()->assertViewIs('auth.login');
        $this->get('/register')->assertOk()->assertViewIs('auth.register');
        $this->get('/admin/login')->assertOk()->assertViewIs('admin.auth.login');

        $this->actingAs($this->customer());

        $this->get('/menu')->assertOk()->assertViewIs('menu.index')->assertSee($product->name);
        $this->get('/menu/'.$product->slug)
            ->assertOk()
            ->assertViewIs('menu.show')
            ->assertSee($product->name)
            ->assertSee('Add to Cart')
            ->assertSee('data-qty', false)
            ->assertSee('data-qty-minus', false)
            ->assertSee('data-qty-plus', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertDontSee('aria-label="breadcrumb"', false)
            ->assertDontSee('breadcrumb-item', false)
            ->assertDontSee('Pairs well with')
            ->assertDontSee('related-thumb', false);
    }

    public function test_brand_logo_and_favicon_reference_the_public_image(): void
    {
        $logoUrl = asset('images/brew-go-logo.png');

        $this->assertFileExists(public_path('images/brew-go-logo.png'));

        $this->get('/')
            ->assertOk()
            ->assertSee('<link rel="icon" type="image/png" href="'.$logoUrl.'">', false)
            ->assertSee('<img src="'.$logoUrl.'"', false);

        $this->actingAs(User::where('email', 'admin@brewandgo.test')->firstOrFail())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<link rel="icon" type="image/png" href="'.$logoUrl.'">', false)
            ->assertSee('<img src="'.$logoUrl.'"', false);
    }

    public function test_password_fields_include_a_visibility_toggle(): void
    {
        $this->assertSame(1, substr_count($this->get('/login')->assertOk()->getContent(), 'data-password-toggle'));
        $this->assertSame(2, substr_count($this->get('/register')->assertOk()->getContent(), 'data-password-toggle'));
        $this->assertSame(1, substr_count($this->get('/admin/login')->assertOk()->getContent(), 'data-password-toggle'));

        $profile = $this->actingAs($this->customer())->get('/profile')->assertOk();
        $this->assertSame(3, substr_count($profile->getContent(), 'data-password-toggle'));
    }

    public function test_brew_buddy_chatbot_is_available_only_on_the_storefront(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-chatbot', false)
            ->assertSee('Brew Buddy')
            ->assertSee('data-chatbot-endpoint="'.route('assistant.reply').'"', false)
            ->assertSee('data-chatbot-toggle', false)
            ->assertSee('data-chatbot-form', false);

        $this->get('/admin/login')
            ->assertOk()
            ->assertDontSee('data-chatbot', false);
    }

    public function test_brew_buddy_answers_store_questions_and_rejects_empty_messages(): void
    {
        $hours = $this->postJson(route('assistant.reply'), ['message' => 'What are your opening hours?'])
            ->assertOk()
            ->assertJsonStructure(['reply', 'links']);

        $this->assertStringContainsString('8:00 AM – 8:00 PM', $hours->json('reply'));

        $payment = $this->postJson(route('assistant.reply'), ['message' => 'How does the pickup down payment work?'])
            ->assertOk();

        $this->assertStringContainsString('down payment', $payment->json('reply'));

        $this->postJson(route('assistant.reply'), ['message' => ''])->assertStatus(422);

        $this->actingAs($this->customer())
            ->postJson(route('assistant.reply'), ['message' => 'Where is my order?'])
            ->assertOk()
            ->assertJsonPath(
                'reply',
                "You don't have an active order right now. Once you place an order, I can check its status for you.",
            );
    }

    public function test_storefront_pages_include_the_live_update_notice(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-live-sync', false)
            ->assertSee('data-live-sync-url="'.route('storefront.version').'"', false)
            ->assertSee('data-live-sync-reload', false)
            ->assertSee('New store updates are available.');
    }

    public function test_storefront_version_changes_when_admin_content_changes(): void
    {
        $first = $this->getJson(route('storefront.version'))->assertOk()->json('version');
        $this->assertNotEmpty($first);
        $this->assertSame($first, $this->getJson(route('storefront.version'))->json('version'));

        $this->travel(2)->seconds();

        $product = Product::query()->firstOrFail();
        $product->update(['name' => $product->name.' Updated']);

        $this->assertNotSame($first, $this->getJson(route('storefront.version'))->json('version'));
    }

    public function test_landing_page_does_not_show_welcome_card_copy(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Welcome to Brew & Go')
            ->assertDontSee('Pull up a chair')
            ->assertDontSee('Welcome back,');

        $this->actingAs($this->customer())
            ->get('/')
            ->assertOk()
            ->assertDontSee('Welcome back,')
            ->assertDontSee('The kettle is on')
            ->assertDontSee('customer@brewandgo.test');
    }

    public function test_landing_page_omits_requested_menu_buttons_and_heading(): void
    {
        $guestResponse = $this->get('/')->assertOk();
        $customerResponse = $this->actingAs($this->customer())->get('/')->assertOk();

        foreach ([$guestResponse, $customerResponse] as $response) {
            $response
                ->assertDontSee('View Full Menu')
                ->assertDontSee('Find your mood')
                ->assertDontSee('not coffee, and everything in between.');
        }
    }

    public function test_public_landing_page_does_not_render_the_menu_section(): void
    {
        $this->assertGreaterThan(8, Product::active()->count());

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertDontSee('id="menu"', false)
            ->assertDontSee('href="'.route('home').'#menu"', false)
            ->assertDontSee('href="'.route('menu.index').'"', false)
            ->assertDontSee('data-product-filter', false)
            ->assertDontSee('data-product-item', false)
            ->assertDontSee('Search the menu')
            ->assertDontSee('Sign in to order');

        $this->assertSame(0, substr_count($response->getContent(), 'class="product-card h-100"'));
    }

    public function test_logged_in_customer_browses_the_dedicated_menu_page(): void
    {
        $customer = $this->customer();
        $activeProducts = Product::active()->count();

        $home = $this->actingAs($customer)->get('/')->assertOk();

        $home
            ->assertDontSee('id="menu"', false)
            ->assertDontSee('data-product-item', false)
            ->assertSee('href="'.route('menu.index').'"', false);

        $this->assertSame(0, substr_count($home->getContent(), 'class="product-card h-100"'));

        $this->get('/menu')
            ->assertOk()
            ->assertSee('Choose size')
            ->assertSee('Select size')
            ->assertDontSee('Customize')
            ->assertDontSee('data-quick-add', false)
            ->assertDontSee('name="quick_add"', false)
            ->assertDontSee('Sign in to order')
            ->assertSee('of '.$activeProducts.' items');
    }

    public function test_dark_and_light_theme_toggle_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('brewgo-theme', false);
    }

    public function test_home_page_provides_single_page_section_navigation(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="home"', false)
            ->assertDontSee('id="menu"', false)
            ->assertSee('id="about"', false)
            ->assertSee('id="contact"', false)
            ->assertSee('href="'.route('home').'#home"', false)
            ->assertDontSee('href="'.route('home').'#menu"', false)
            ->assertSee('href="'.route('home').'#about"', false)
            ->assertSee('href="'.route('home').'#contact"', false)
            ->assertDontSee('href="'.route('menu.index').'"', false)
            ->assertSee('Good coffee.')
            ->assertSee('Come say')
            ->assertSee('Zone 1, Bangued, Abra')
            ->assertSee('+63 995 524 3015')
            ->assertSee('kbbersalona@gmail.com')
            ->assertSee('8:00 AM – 8:00 PM')
            ->assertDontSee('Contact details are being updated.');

        $this->actingAs($this->customer())
            ->get('/')
            ->assertOk()
            ->assertSee('href="'.route('menu.index').'"', false)
            ->assertSee('>Menu</a>', false);
    }

    public function test_menu_hides_starting_prices_until_a_size_is_selected(): void
    {
        $product = Product::where('slug', 'iced-americano')->firstOrFail();
        $product->options()->where('group_name', 'size')->where('name', 'Regular')->update(['price' => 45]);
        $product->options()->where('group_name', 'size')->where('name', 'Large')->update(['price' => 80, 'is_active' => false]);

        $this->actingAs($this->customer());

        $this->get('/menu')
            ->assertOk()
            ->assertSee('Select size')
            ->assertDontSee('from</small>', false)
            ->assertDontSee('₱45.00')
            ->assertDontSee('₱80.00');

        $this->get('/menu/'.$product->slug)
            ->assertOk()
            ->assertSee('Select a size to continue.')
            ->assertSee('Regular')
            ->assertDontSee('From')
            ->assertDontSee('depending on size')
            ->assertDontSee('₱80.00');

        $heroProduct = Product::active()->featured()->with('options')->orderBy('name')->firstOrFail();
        $heroProduct->options()
            ->where('group_name', 'size')
            ->where('name', 'Regular')
            ->update(['price' => 70]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Choose size')
            ->assertDontSee('From ₱70.00');
    }

    public function test_menu_provides_instant_search_for_every_active_product(): void
    {
        $products = Product::active()->with('category')->orderBy('name')->get();
        $matcha = $products->firstWhere('slug', 'matcha-cloud');

        $response = $this->actingAs($this->customer())->get('/menu?search=Matcha');

        $response
            ->assertOk()
            ->assertSee('data-product-filter', false)
            ->assertSee('id="menuSearch"', false)
            ->assertSee('value="Matcha"', false)
            ->assertSee('data-product-search', false)
            ->assertSee('data-product-empty', false)
            ->assertSee('data-product-reset', false)
            ->assertSee('Search by name, description, or category')
            ->assertSee('data-menu-category', false)
            ->assertSee('data-product-category', false)
            ->assertDontSee('filter-pill', false);

        $this->assertSame($products->count(), substr_count($response->getContent(), 'data-product-item'));

        $searchText = Str::lower($matcha->name.' '.$matcha->description.' '.$matcha->category->name);

        $response->assertSee('data-product-search-text="'.$searchText.'"', false);
    }

    public function test_menu_loads_category_filters_from_the_database(): void
    {
        $customer = $this->customer();
        $categories = Category::query()
            ->active()
            ->ordered()
            ->get();

        $this->assertNotEmpty($categories);

        $response = $this->actingAs($customer)
            ->get('/menu')
            ->assertOk()
            ->assertSee('data-menu-category=""', false)
            ->assertSee('>All</a>', false);

        foreach ($categories as $category) {
            $response
                ->assertSee('data-menu-category="'.$category->slug.'"', false)
                ->assertSee($category->name);
        }
    }

    public function test_menu_only_shows_active_categories_and_their_own_products_without_duplicates(): void
    {
        $customer = $this->customer();

        $hiddenCategory = Category::create([
            'name' => 'Retired Drinks',
            'slug' => 'retired-drinks',
            'description' => 'No longer offered.',
            'is_active' => false,
            'sort_order' => 90,
        ]);

        $hiddenProduct = Product::create([
            'category_id' => $hiddenCategory->id,
            'name' => 'Retired Mocha',
            'slug' => 'retired-mocha',
            'description' => 'A hidden product.',
            'base_price' => 99,
            'is_active' => true,
        ]);

        $visibleCategory = Category::create([
            'name' => 'Seasonal Refreshers',
            'slug' => 'seasonal-refreshers',
            'description' => 'Limited-time drinks.',
            'is_active' => true,
            'sort_order' => 91,
        ]);

        $visibleProduct = Product::create([
            'category_id' => $visibleCategory->id,
            'name' => 'Mango Cold Brew',
            'slug' => 'mango-cold-brew',
            'description' => 'A seasonal favorite.',
            'base_price' => 149,
            'is_active' => true,
        ]);

        $activeCategories = Category::query()->active()->ordered()->get();

        $response = $this->actingAs($customer)->get('/menu')->assertOk();
        $html = $response->getContent();

        $this->assertSame($activeCategories->count(), substr_count($html, 'class="menu-category-chip"'));

        foreach ($activeCategories as $category) {
            $this->assertSame(1, substr_count($html, 'data-menu-category="'.$category->slug.'"'));
        }

        $response
            ->assertSee($visibleProduct->name)
            ->assertSee('data-product-category="'.$visibleCategory->slug.'"', false)
            ->assertDontSee('data-menu-category="'.$hiddenCategory->slug.'"', false)
            ->assertDontSee($hiddenProduct->name);
    }

    public function test_menu_shows_every_active_category_even_when_it_has_no_products(): void
    {
        $customer = $this->customer();

        $category = Category::create([
            'name' => 'Tea Bar',
            'slug' => 'tea-bar',
            'description' => 'Coming soon.',
            'is_active' => true,
            'sort_order' => 95,
        ]);

        $this->assertFalse($category->products()->exists());

        $this->actingAs($customer)
            ->get('/menu')
            ->assertOk()
            ->assertSee('data-menu-category="'.$category->slug.'"', false)
            ->assertSee($category->name);
    }

    public function test_menu_highlights_the_selected_category_without_hiding_other_products(): void
    {
        $customer = $this->customer();
        $nonCoffee = Category::where('slug', 'non-coffee')->firstOrFail();
        $selectedProducts = Product::active()->where('category_id', $nonCoffee->id)->get();
        $otherProduct = Product::active()
            ->where('category_id', '!=', $nonCoffee->id)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->firstOrFail();
        $activeProductCount = Product::active()
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->count();

        $this->assertNotEmpty($selectedProducts);

        $response = $this->actingAs($customer)
            ->get('/menu?category='.$nonCoffee->slug)
            ->assertOk()
            ->assertSee('data-menu-category="'.$nonCoffee->slug.'"', false)
            ->assertSee('class="menu-category-chip is-active"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee($otherProduct->name);

        foreach ($selectedProducts as $product) {
            $response->assertSee($product->name);
        }

        $html = $response->getContent();

        $this->assertSame($activeProductCount, substr_count($html, 'data-product-item'));
        $this->assertGreaterThan(0, substr_count($html, 'data-product-category="'.$nonCoffee->slug.'"'));
        $this->assertGreaterThan(0, substr_count($html, 'data-product-category="'.$otherProduct->category->slug.'"'));
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
    }

    public function test_menu_falls_back_to_all_products_for_an_unknown_category(): void
    {
        $customer = $this->customer();
        $activeProducts = Product::active()
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->count();

        $response = $this->actingAs($customer)->get('/menu?category=does-not-exist')->assertOk();

        $this->assertSame($activeProducts, substr_count($response->getContent(), 'data-product-item'));
        $response->assertSee('>All</a>', false);
    }

    public function test_inactive_category_hides_its_products_from_the_menu_and_product_page(): void
    {
        $customer = $this->customer();
        $food = Category::where('slug', 'food')->firstOrFail();
        $foodProduct = Product::active()->where('category_id', $food->id)->firstOrFail();

        $this->actingAs($customer)->get('/menu/'.$foodProduct->slug)->assertOk();

        $food->update(['is_active' => false]);

        $this->actingAs($customer)
            ->get('/menu')
            ->assertOk()
            ->assertDontSee('data-menu-category="'.$food->slug.'"', false)
            ->assertDontSee('data-product-category="'.$food->slug.'"', false);

        $this->actingAs($customer)->get('/menu/'.$foodProduct->slug)->assertNotFound();
    }

    public function test_inactive_category_hides_its_featured_products_from_the_home_page(): void
    {
        $customer = $this->customer();
        $featuredProduct = Product::active()
            ->featured()
            ->with('category')
            ->orderBy('name')
            ->firstOrFail();

        $this->actingAs($customer)->get('/')->assertOk()->assertSee($featuredProduct->name);

        $featuredProduct->category->update(['is_active' => false]);

        $this->actingAs($customer)
            ->get('/')
            ->assertOk()
            ->assertDontSee($featuredProduct->name);
    }

    public function test_guests_are_redirected_to_login_from_protected_pages(): void
    {
        $product = Product::where('slug', 'brew-and-go-signature')->firstOrFail();

        $this->get('/menu')->assertRedirect(route('login'));
        $this->get('/menu/'.$product->slug)->assertRedirect(route('login'));
        $this->get('/cart')->assertRedirect(route('login'));
        $this->get('/checkout')->assertRedirect(route('login'));
        $this->get('/orders')->assertRedirect(route('login'));
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_customer_can_login_and_logout(): void
    {
        $customer = User::where('email', 'customer@brewandgo.test')->firstOrFail();

        $this->post('/login', [
            'email' => $customer->email,
            'password' => 'Customer@12345',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($customer);

        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_inactive_customer_cannot_login(): void
    {
        $customer = User::where('email', 'customer@brewandgo.test')->firstOrFail();
        $customer->update(['is_active' => false]);

        $this->from('/login')->post('/login', [
            'email' => $customer->email,
            'password' => 'Customer@12345',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_customer_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Guest',
            'email' => 'newguest@example.com',
            'phone' => '09175551234',
            'password' => 'Newpass1A',
            'password_confirmation' => 'Newpass1A',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'newguest@example.com',
            'is_admin' => false,
            'is_active' => true,
        ]);
    }

    public function test_customer_can_update_profile_and_password(): void
    {
        $customer = User::where('email', 'customer@brewandgo.test')->firstOrFail();
        $this->actingAs($customer);

        $this->put('/profile', [
            'name' => 'Updated Customer',
            'email' => 'customer@brewandgo.test',
            'phone' => '09189998888',
            'address' => 'Updated Address',
            'current_password' => 'Customer@12345',
            'password' => 'NewCustomer2B',
            'password_confirmation' => 'NewCustomer2B',
        ])->assertRedirect(route('profile.edit'));

        $customer->refresh();
        $this->assertSame('Updated Customer', $customer->name);
        $this->assertSame('09189998888', $customer->phone);
        $this->assertSame('Updated Address', $customer->address);
        $this->assertTrue(Hash::check('NewCustomer2B', $customer->password));
    }

    private function customer(): User
    {
        return User::where('email', 'customer@brewandgo.test')->firstOrFail();
    }
}

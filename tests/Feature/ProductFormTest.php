<?php

namespace Tests\Feature;

use App\Http\Middleware\PreventDuplicateProductSubmission;
use App\Models\Addon;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductImageService;
use App\Services\ProductSizePriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProductFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seedTestCatalog();
        $this->createCustomer();
    }

    public function test_validation_failure_preserves_entered_product_data(): void
    {
        $admin = $this->admin();
        $category = Category::where('slug', 'coffee')->firstOrFail();
        $addon = Addon::where('name', 'Oat Milk')->firstOrFail();

        $response = $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->payload([
                'name' => 'Draft Latte',
                'slug' => 'draft-latte',
                'description' => '',
                'size_prices' => [
                    'small' => 150,
                    'medium' => 170,
                    'large' => 190,
                ],
            ]));

        $response
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('description')
            ->assertSessionHasInput('name', 'Draft Latte')
            ->assertSessionHasInput('category_id', (string) $category->id)
            ->assertSessionHasInput('size_prices.medium', '170')
            ->assertSessionHasInput('addons.0', (string) $addon->id);
    }

    public function test_validation_failure_renders_the_entered_values_again(): void
    {
        $admin = $this->admin();
        $category = Category::where('slug', 'coffee')->firstOrFail();
        $addon = Addon::where('name', 'Oat Milk')->firstOrFail();

        $this->actingAs($admin)
            ->followingRedirects()
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->payload([
                'name' => 'Draft Latte',
                'slug' => 'draft-latte',
                'description' => '',
                'size_prices' => [
                    'small' => 150,
                    'medium' => 170,
                    'large' => 190,
                ],
            ]))
            ->assertOk()
            ->assertSee('value="Draft Latte"', false)
            ->assertSee('value="170"', false)
            ->assertSee('value="'.$category->id.'" selected', false)
            ->assertSee('value="'.$addon->id.'" checked', false);
    }

    public function test_successful_creation_confirms_the_saved_product_and_resets_the_form(): void
    {
        $admin = $this->admin();
        $token = $this->createFormToken();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), $this->payload([
            'submission_token' => $token,
        ]));

        $product = Product::where('slug', 'form-test-latte')->firstOrFail();

        $response
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHas('success', 'Product "Form Test Latte" was created successfully. The form is ready for the next product.');

        $this->assertSame(170.0, (float) $product->base_price);
        $this->assertSame(3, $product->options()->where('group_name', 'size')->count());
        $this->assertTrue($product->addons()->whereKey($this->addon()->id)->exists());
        $this->assertSame(1, Product::where('slug', 'form-test-latte')->count());

        $this->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('was created successfully.')
            ->assertDontSee('value="Form Test Latte"', false);

        $nextToken = $this->app['session.store']->get(PreventDuplicateProductSubmission::SESSION_KEY);

        $this->assertIsString($nextToken);
        $this->assertNotSame($token, $nextToken);
        $this->assertTrue($product->fresh()->exists);
    }

    public function test_saving_another_product_keeps_the_previous_one_untouched(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload([
            'name' => 'First Latte',
            'slug' => 'first-latte',
            'submission_token' => $this->createFormToken(),
        ]))->assertRedirect(route('admin.products.create'));

        $first = Product::where('slug', 'first-latte')->firstOrFail();
        $firstAddonIds = $first->addons()->pluck('addons.id')->sort()->values()->all();
        $firstSizeCount = $first->options()->where('group_name', 'size')->count();

        $this->get(route('admin.products.create'))->assertOk();

        $secondToken = $this->app['session.store']->get(PreventDuplicateProductSubmission::SESSION_KEY);

        $this->assertIsString($secondToken);

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload([
            'name' => 'Second Latte',
            'slug' => 'second-latte',
            'submission_token' => $secondToken,
        ]))->assertRedirect(route('admin.products.create'));

        $first->refresh();

        $this->assertSame('First Latte', $first->name);
        $this->assertSame(170.0, (float) $first->base_price);
        $this->assertTrue($first->is_active);
        $this->assertSame($firstAddonIds, $first->addons()->pluck('addons.id')->sort()->values()->all());
        $this->assertSame($firstSizeCount, $first->options()->where('group_name', 'size')->count());
        $this->assertSame(1, Product::where('slug', 'first-latte')->count());
        $this->assertDatabaseHas('products', ['slug' => 'second-latte', 'name' => 'Second Latte']);
        $this->assertSame(1, Product::where('slug', 'second-latte')->count());
    }

    public function test_validation_failure_can_be_retried_with_the_same_form_token(): void
    {
        $admin = $this->admin();
        $token = $this->createFormToken();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload([
            'description' => '',
            'submission_token' => $token,
        ]))->assertSessionHasErrors('description');

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload([
            'submission_token' => $token,
        ]))->assertRedirect(route('admin.products.create'));

        $this->assertSame(1, Product::where('slug', 'form-test-latte')->count());
    }

    public function test_replaying_a_used_submission_token_does_not_create_a_duplicate(): void
    {
        $admin = $this->admin();
        $token = $this->createFormToken();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->payload(['submission_token' => $token]))
            ->assertRedirect();

        $product = Product::where('slug', 'form-test-latte')->firstOrFail();
        $savedState = $product->only(['name', 'slug', 'base_price']);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->payload(['submission_token' => $token]))
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHas('error')
            ->assertSessionMissing('_old_input');

        $this->assertSame(1, Product::where('slug', 'form-test-latte')->count());
        $this->assertSame($savedState, $product->fresh()->only(array_keys($savedState)));
    }

    public function test_save_failure_preserves_input_cleans_up_the_image_and_allows_a_retry(): void
    {
        Storage::fake(ProductImageService::DISK);

        $sizePrices = Mockery::mock(ProductSizePriceService::class);
        $sizePrices->shouldReceive('sync')->once()->andThrow(new RuntimeException('Database connection lost.'));
        $this->app->instance(ProductSizePriceService::class, $sizePrices);

        $admin = $this->admin();
        $token = $this->createFormToken();
        $addon = $this->addon();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), $this->payload([
            'name' => 'Failure Latte',
            'slug' => 'failure-latte',
            'submission_token' => $token,
            'image_file' => $this->fakePng('failure-latte.png'),
        ]));

        $response
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHas('error')
            ->assertSessionHasInput('name', 'Failure Latte')
            ->assertSessionHasInput('category_id', (string) $this->category()->id)
            ->assertSessionHasInput('size_prices.medium', '170')
            ->assertSessionHasInput('addons.0', (string) $addon->id);

        $this->assertDatabaseMissing('products', ['slug' => 'failure-latte']);
        $this->assertSame([], Storage::disk(ProductImageService::DISK)->allFiles());

        $retryToken = $this->app['session.store']->get(PreventDuplicateProductSubmission::SESSION_KEY);

        $this->assertIsString($retryToken);
        $this->assertNotSame($token, $retryToken);
        $this->assertSame($retryToken, session('_old_input.submission_token'));
    }

    public function test_create_form_has_duplicate_guard_and_image_preview_hooks(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('name="submission_token"', false)
            ->assertSee('data-image-preview', false)
            ->assertSee('data-submit-once', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'category_id' => $this->category()->id,
            'name' => 'Form Test Latte',
            'slug' => '',
            'description' => 'A product created from the admin form.',
            'size_prices' => [
                'small' => 150,
                'medium' => 170,
                'large' => 190,
            ],
            'is_featured' => 1,
            'is_active' => 1,
            'addons' => [$this->addon()->id],
        ], $overrides);
    }

    private function createFormToken(): string
    {
        $this->actingAs($this->admin())
            ->get(route('admin.products.create'))
            ->assertOk();

        $token = $this->app['session.store']->get(PreventDuplicateProductSubmission::SESSION_KEY);

        $this->assertIsString($token);

        return $token;
    }

    private function admin(): User
    {
        return User::where('email', 'admin@brewandgo.test')->firstOrFail();
    }

    private function category(): Category
    {
        return Category::where('slug', 'coffee')->firstOrFail();
    }

    private function addon(): Addon
    {
        return Addon::where('name', 'Oat Milk')->firstOrFail();
    }

    private function fakePng(string $name): UploadedFile
    {
        $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZlS8AAAAASUVORK5CYII=', true);

        return UploadedFile::fake()->createWithContent($name, $contents);
    }
}

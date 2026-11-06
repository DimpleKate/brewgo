<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Promotion;
use App\Models\User;
use App\Services\ContentImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seedTestCatalog();
        $this->createCustomer();
        Storage::fake(ContentImageService::DISK);
    }

    public function test_category_and_promotion_images_are_managed_and_cleaned_up(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Seasonal',
            'description' => 'Banner test category.',
            'is_active' => 1,
            'sort_order' => 2,
            'image_file' => $this->fakePng('seasonal-category.png'),
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::where('slug', 'seasonal')->firstOrFail();
        $categoryImage = $category->image;

        $this->assertStringStartsWith('images/categories/', $categoryImage);
        Storage::disk(ContentImageService::DISK)->assertExists('categories/'.basename($categoryImage));

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee(asset($categoryImage));

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        Storage::disk(ContentImageService::DISK)->assertMissing('categories/'.basename($categoryImage));

        $this->actingAs($admin)->post(route('admin.promotions.store'), [
            'title' => 'Banner Promotion',
            'description' => 'A promotion used to test image cleanup.',
            'discount_type' => 'percent',
            'discount_value' => 90,
            'minimum_spend' => 0,
            'is_active' => 1,
            'image_file' => $this->fakePng('promotion-banner.png'),
        ])->assertRedirect(route('admin.promotions.index'));

        $promotion = Promotion::where('title', 'Banner Promotion')->firstOrFail();
        $promotionImage = $promotion->image;

        $this->assertStringStartsWith('images/promotions/', $promotionImage);
        Storage::disk(ContentImageService::DISK)->assertExists('promotions/'.basename($promotionImage));

        $this->post(route('admin.logout'));

        $this->get('/')->assertOk()->assertSee(asset($promotionImage));

        $this->actingAs($admin)->patch(route('admin.promotions.update', $promotion), [
            'title' => 'Banner Promotion',
            'description' => 'A promotion used to test image cleanup.',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'minimum_spend' => 0,
            'is_active' => 1,
            'remove_image' => 1,
        ])->assertRedirect(route('admin.promotions.index'));

        $this->assertNull($promotion->refresh()->image);
        Storage::disk(ContentImageService::DISK)->assertMissing('promotions/'.basename($promotionImage));
    }

    private function admin(): User
    {
        return User::where('email', 'admin@brewandgo.test')->firstOrFail();
    }

    private function fakePng(string $name): UploadedFile
    {
        $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZlS8AAAAASUVORK5CYII=', true);

        return UploadedFile::fake()->createWithContent($name, $contents);
    }
}

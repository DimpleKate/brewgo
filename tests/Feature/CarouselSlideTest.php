<?php

namespace Tests\Feature;

use App\Models\CarouselSlide;
use App\Models\User;
use App\Services\ContentImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarouselSlideTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_shows_active_slides_in_order_and_hides_inactive_ones(): void
    {
        CarouselSlide::create([
            'title' => 'Second Slide',
            'image' => 'images/carousel/second.png',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        CarouselSlide::create([
            'title' => 'First Slide',
            'description' => 'The first carousel message.',
            'image' => 'images/carousel/first.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        CarouselSlide::create([
            'title' => 'Hidden Slide',
            'image' => 'images/carousel/hidden.png',
            'is_active' => false,
            'sort_order' => 0,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['First Slide', 'Second Slide'])
            ->assertSee('The first carousel message.')
            ->assertSee(asset('images/carousel/first.png'))
            ->assertSee('data-coverflow-prev', false)
            ->assertSee('data-coverflow-next', false)
            ->assertSee('data-coverflow-dots', false)
            ->assertDontSee('Hidden Slide');

        $this->assertSame(
            ['First Slide', 'Second Slide'],
            CarouselSlide::active()->ordered()->pluck('title')->all()
        );
    }

    public function test_admin_carousel_page_only_exposes_image_upload_preview_and_delete_controls(): void
    {
        Storage::fake(ContentImageService::DISK);
        $this->seed();
        $admin = User::where('email', 'admin@brewandgo.test')->firstOrFail();

        $slide = CarouselSlide::create([
            'title' => 'Existing slide',
            'image' => 'images/carousel/existing.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.carousel.index'))
            ->assertOk()
            ->assertSee('name="images[]"', false)
            ->assertSee('data-carousel-preview', false)
            ->assertSee('Upload carousel images')
            ->assertSee('value="'.$slide->id.'"', false)
            ->assertSee('Delete selected')
            ->assertDontSee('Single slide with details')
            ->assertDontSee('Image path or URL')
            ->assertDontSee('name="image_file"', false)
            ->assertDontSee('name="title"', false)
            ->assertDontSee('name="description"', false)
            ->assertDontSee('name="link_url"', false)
            ->assertDontSee('name="link_label"', false)
            ->assertDontSee('name="sort_order"', false)
            ->assertDontSee('Visible on storefront')
            ->assertDontSee('Edit details')
            ->assertDontSee('Move image earlier')
            ->assertDontSee('Move image later');

        $this->actingAs($admin)->get('/admin/carousel/create')->assertMethodNotAllowed();
    }

    public function test_admin_can_bulk_upload_images_without_titles_and_they_appear_on_the_homepage(): void
    {
        Storage::fake(ContentImageService::DISK);
        $this->seed();
        $admin = User::where('email', 'admin@brewandgo.test')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.carousel.store-many'), [
                'images' => [
                    $this->fakePng('bulk-one.png'),
                    $this->fakePng('bulk-two.png'),
                ],
            ])
            ->assertRedirect(route('admin.carousel.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', '2 carousel images uploaded and added to the homepage slider.');

        $slides = CarouselSlide::ordered()->get();

        $this->assertCount(2, $slides);
        $this->assertSame([null, null], $slides->pluck('title')->all());
        $this->assertSame([1, 2], $slides->pluck('sort_order')->all());
        $this->assertTrue($slides->every(fn (CarouselSlide $slide) => $slide->is_active));

        foreach ($slides as $slide) {
            $this->assertStringStartsWith('images/carousel/', $slide->image);
            Storage::disk(ContentImageService::DISK)->assertExists('carousel/'.basename($slide->image));
        }

        $this->post(route('admin.logout'));

        $this->get('/')
            ->assertOk()
            ->assertSee(asset($slides[0]->image))
            ->assertSee(asset($slides[1]->image))
            ->assertSee('Brew &amp; Go featured moment', false);

        $this->actingAs($admin)
            ->get(route('admin.carousel.index'))
            ->assertOk()
            ->assertSee('data-carousel-select', false)
            ->assertSee('data-carousel-select-all', false)
            ->assertSee('data-carousel-bulk-submit', false)
            ->assertSee('Delete selected')
            ->assertSee('value="'.$slides[0]->id.'"', false)
            ->assertSee('value="'.$slides[1]->id.'"', false);
    }

    public function test_bulk_carousel_upload_requires_at_least_one_valid_image_and_an_admin(): void
    {
        Storage::fake(ContentImageService::DISK);
        $this->seed();
        $admin = User::where('email', 'admin@brewandgo.test')->firstOrFail();
        $customer = $this->createCustomer();

        $this->actingAs($admin)
            ->post(route('admin.carousel.store-many'), [])
            ->assertSessionHasErrors('images');

        $this->actingAs($admin)
            ->post(route('admin.carousel.store-many'), [
                'images' => [UploadedFile::fake()->createWithContent('notes.txt', 'not an image')],
            ])
            ->assertSessionHasErrors('images.0');

        $this->actingAs($customer)
            ->post(route('admin.carousel.store-many'), [
                'images' => [$this->fakePng('blocked-bulk.png')],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('carousel_slides', 0);
    }

    public function test_admin_can_delete_carousel_images_individually_and_in_bulk(): void
    {
        Storage::fake(ContentImageService::DISK);
        $this->seed();
        $admin = User::where('email', 'admin@brewandgo.test')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.carousel.store-many'), [
                'images' => [
                    $this->fakePng('delete-one.png'),
                    $this->fakePng('delete-two.png'),
                    $this->fakePng('delete-three.png'),
                ],
            ])
            ->assertRedirect(route('admin.carousel.index'));

        $slides = CarouselSlide::ordered()->get();
        $this->assertCount(3, $slides);

        $first = $slides[0];
        $firstImage = basename($first->image);
        $remaining = $slides->slice(1)->values();
        $remainingImages = $remaining->map(fn (CarouselSlide $slide) => basename($slide->image))->all();

        $this->actingAs($admin)
            ->delete(route('admin.carousel.destroy', $first))
            ->assertRedirect(route('admin.carousel.index'))
            ->assertSessionHas('success', 'Carousel image deleted.');

        $this->assertDatabaseMissing('carousel_slides', ['id' => $first->id]);
        Storage::disk(ContentImageService::DISK)->assertMissing('carousel/'.$firstImage);

        $this->actingAs($admin)
            ->delete(route('admin.carousel.destroy-many'), [
                'ids' => $remaining->pluck('id')->all(),
            ])
            ->assertRedirect(route('admin.carousel.index'))
            ->assertSessionHas('success', '2 carousel images deleted.');

        $this->assertDatabaseCount('carousel_slides', 0);

        foreach ($remainingImages as $image) {
            Storage::disk(ContentImageService::DISK)->assertMissing('carousel/'.$image);
        }

        $this->post(route('admin.logout'));

        $this->get('/')->assertOk()->assertDontSee('data-coverflow', false);
    }

    public function test_bulk_carousel_delete_requires_selected_ids_and_an_admin(): void
    {
        Storage::fake(ContentImageService::DISK);
        $this->seed();
        $admin = User::where('email', 'admin@brewandgo.test')->firstOrFail();
        $customer = $this->createCustomer();

        $slide = CarouselSlide::create([
            'image' => 'images/carousel/keep-me.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.carousel.destroy-many'), [])
            ->assertSessionHasErrors('ids');

        $this->actingAs($admin)
            ->delete(route('admin.carousel.destroy-many'), ['ids' => [999999]])
            ->assertSessionHasErrors('ids.0');

        $this->actingAs($customer)
            ->delete(route('admin.carousel.destroy-many'), ['ids' => [$slide->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('carousel_slides', ['id' => $slide->id]);
    }

    private function fakePng(string $name): UploadedFile
    {
        $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZlS8AAAAASUVORK5CYII=', true);

        return UploadedFile::fake()->createWithContent($name, $contents);
    }
}

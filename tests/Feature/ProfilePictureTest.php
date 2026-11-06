<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ProfilePictureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePictureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->createCustomer();
        Storage::fake(ProfilePictureService::DISK);
    }

    public function test_customer_can_upload_replace_and_remove_a_profile_picture(): void
    {
        $customer = User::where('email', 'customer@brewandgo.test')->firstOrFail();

        $this->actingAs($customer)
            ->put(route('profile.avatar.update'), [
                'avatar' => $this->fakePng('customer-avatar.png'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success', 'Your profile picture has been updated.');

        $customer->refresh();
        $firstPath = $customer->avatar_path;

        $this->assertNotNull($firstPath);
        $this->assertStringStartsWith(ProfilePictureService::DIRECTORY.'/', $firstPath);
        Storage::disk(ProfilePictureService::DISK)->assertExists(basename($firstPath));

        $this->actingAs($customer)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee($customer->avatarUrl())
            ->assertSee('Replace picture');

        $this->actingAs($customer)
            ->get(route('menu.index'))
            ->assertOk()
            ->assertSee($customer->avatarUrl());

        $this->actingAs($customer)
            ->put(route('profile.avatar.update'), [
                'avatar' => $this->fakePng('replacement-avatar.png'),
            ])
            ->assertRedirect(route('profile.edit'));

        $customer->refresh();
        $secondPath = $customer->avatar_path;

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk(ProfilePictureService::DISK)->assertMissing(basename($firstPath));
        Storage::disk(ProfilePictureService::DISK)->assertExists(basename($secondPath));

        $this->actingAs($customer)
            ->delete(route('profile.avatar.destroy'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success', 'Your profile picture has been removed.');

        $this->assertNull($customer->refresh()->avatar_path);
        Storage::disk(ProfilePictureService::DISK)->assertMissing(basename($secondPath));

        $this->actingAs($customer)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Upload picture');
    }

    public function test_admin_can_update_their_own_profile_picture_without_affecting_customers(): void
    {
        $admin = User::where('email', 'admin@brewandgo.test')->firstOrFail();
        $customer = User::where('email', 'customer@brewandgo.test')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('profile.avatar.update'), [
                'avatar' => $this->fakePng('admin-avatar.png'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasNoErrors();

        $admin->refresh();

        $this->assertNotNull($admin->avatar_path);
        $this->assertNull($customer->refresh()->avatar_path);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($admin->avatarUrl())
            ->assertSee('My profile');
    }

    public function test_profile_picture_upload_rejects_invalid_files(): void
    {
        $customer = User::where('email', 'customer@brewandgo.test')->firstOrFail();

        $this->actingAs($customer)
            ->from(route('profile.edit'))
            ->put(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->createWithContent('notes.txt', 'not an image'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('avatar');

        $this->actingAs($customer)
            ->from(route('profile.edit'))
            ->put(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->create('too-large.jpg', 6000, 'image/jpeg'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('avatar');

        $this->assertNull($customer->refresh()->avatar_path);
    }

    public function test_profile_picture_update_only_changes_the_authenticated_user(): void
    {
        $customer = User::where('email', 'customer@brewandgo.test')->firstOrFail();
        $admin = User::where('email', 'admin@brewandgo.test')->firstOrFail();

        $this->actingAs($customer)
            ->put(route('profile.avatar.update'), [
                'avatar' => $this->fakePng('own-avatar.png'),
                'user_id' => $admin->id,
                'is_admin' => 1,
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($customer->refresh()->avatar_path);
        $this->assertNull($admin->refresh()->avatar_path);
        $this->assertFalse($customer->is_admin);
        $this->assertTrue($admin->is_admin);
    }

    public function test_guests_cannot_change_a_profile_picture(): void
    {
        $this->put(route('profile.avatar.update'), [
            'avatar' => $this->fakePng('guest-avatar.png'),
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('users', 2);
    }

    private function fakePng(string $name): UploadedFile
    {
        $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZlS8AAAAASUVORK5CYII=', true);

        return UploadedFile::fake()->createWithContent($name, $contents);
    }
}

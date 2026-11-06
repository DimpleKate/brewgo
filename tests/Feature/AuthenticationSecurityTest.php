<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seedTestCatalog();
        $this->createCustomer();
    }

    public function test_admin_logging_in_through_the_customer_login_lands_on_the_admin_dashboard(): void
    {
        $this->post(route('login.store'), [
            'email' => 'admin@brewandgo.test',
            'password' => 'Admin@12345',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($this->admin());
    }

    public function test_customer_login_still_lands_on_the_storefront(): void
    {
        $this->post(route('login.store'), [
            'email' => 'customer@brewandgo.test',
            'password' => 'Customer@12345',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($this->customer());
    }

    public function test_admin_is_sent_from_the_public_landing_page_to_the_dashboard(): void
    {
        $this->get(route('home'))->assertOk()->assertViewIs('home');

        $this->actingAs($this->customer())
            ->get(route('home'))
            ->assertOk()
            ->assertViewIs('home');

        $this->actingAs($this->admin())
            ->get(route('home'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_customer_logout_invalidates_the_session_and_blocks_protected_pages(): void
    {
        $this->actingAs($this->customer());

        $sessionId = session()->getId();
        $csrfToken = session()->token();

        $this->post(route('logout'))->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame($csrfToken, session()->token());

        $this->get(route('orders.index'))->assertRedirect(route('login'));
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->get(route('cart.index'))->assertRedirect(route('login'));
    }

    public function test_admin_logout_invalidates_the_session_and_blocks_the_console(): void
    {
        $this->actingAs($this->admin());

        $sessionId = session()->getId();

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_protected_responses_are_not_cached_by_the_browser(): void
    {
        $this->actingAs($this->customer());

        $orders = $this->get(route('orders.index'))->assertOk();
        $this->assertNoStore($orders);
        $orders->assertHeader('Pragma', 'no-cache');

        $profile = $this->get(route('profile.edit'))->assertOk();
        $this->assertNoStore($profile);
    }

    public function test_admin_console_responses_are_not_cached_by_the_browser(): void
    {
        $this->actingAs($this->admin());

        $dashboard = $this->get(route('admin.dashboard'))->assertOk();
        $this->assertNoStore($dashboard);
        $dashboard->assertHeader('Expires', '0');
    }

    private function assertNoStore(TestResponse $response): void
    {
        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@brewandgo.test')->firstOrFail();
    }

    private function customer(): User
    {
        return User::where('email', 'customer@brewandgo.test')->firstOrFail();
    }
}

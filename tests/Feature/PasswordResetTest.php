<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'name' => 'Brew Tester',
            'email' => 'brew.tester@example.com',
            'password' => Hash::make('OldPassword@123'),
        ]);

        $this->admin = User::factory()->admin()->create([
            'name' => 'Brew Administrator',
            'email' => 'brew.admin@example.com',
            'password' => Hash::make('AdminOld@123'),
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    public function test_forgot_password_page_can_be_opened(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertViewIs('auth.forgot-password')
            ->assertSee('Email or username');
    }

    public function test_unknown_email_or_username_shows_an_error(): void
    {
        $this->from(route('password.request'))
            ->post(route('password.email'), [
                'identifier' => 'missing@example.com',
            ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors([
                'identifier' => 'We could not find an account with that email or username.',
            ]);

        $this->assertNull(session('password_reset'));
    }

    public function test_registered_email_opens_the_new_password_page(): void
    {
        $response = $this->post(route('password.email'), [
            'identifier' => 'brew.tester@example.com',
        ]);

        $token = session('password_reset.token');

        $response->assertRedirect(route('password.reset', ['token' => $token]));

        $this->get(route('password.reset', ['token' => $token]))
            ->assertOk()
            ->assertViewIs('auth.reset-password')
            ->assertSee('brew.tester@example.com')
            ->assertSee('New password');
    }

    public function test_registered_username_opens_the_new_password_page(): void
    {
        $response = $this->post(route('password.email'), [
            'identifier' => 'Brew Tester',
        ]);

        $token = session('password_reset.token');

        $response->assertRedirect(route('password.reset', ['token' => $token]));

        $this->get(route('password.reset', ['token' => $token]))
            ->assertOk()
            ->assertViewIs('auth.reset-password');
    }

    public function test_user_can_set_and_confirm_a_new_password_and_sign_in_with_it(): void
    {
        Notification::fake();

        $this->post(route('password.email'), [
            'identifier' => 'brew.tester@example.com',
        ]);

        $token = session('password_reset.token');

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'password' => 'NewPassword@456',
            'password_confirmation' => 'NewPassword@456',
        ]);

        $response->assertRedirect(route('login'))->assertSessionHas('success');

        $this->customer->refresh();

        $this->assertTrue(Hash::check('NewPassword@456', $this->customer->password));
        $this->assertFalse(Hash::check('OldPassword@123', $this->customer->password));
        $this->assertNull(session('password_reset'));
        Notification::assertNothingSent();

        $this->post(route('login.store'), [
            'email' => 'brew.tester@example.com',
            'password' => 'NewPassword@456',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($this->customer);
    }

    public function test_new_password_also_works_with_username_login(): void
    {
        $this->post(route('password.email'), [
            'identifier' => 'Brew Tester',
        ]);

        $this->post(route('password.update'), [
            'token' => session('password_reset.token'),
            'password' => 'NewPassword@456',
            'password_confirmation' => 'NewPassword@456',
        ])->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => 'Brew Tester',
            'password' => 'NewPassword@456',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($this->customer);
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->post(route('password.email'), [
            'identifier' => 'brew.tester@example.com',
        ]);

        $this->post(route('password.update'), [
            'token' => session('password_reset.token'),
            'password' => 'NewPassword@456',
            'password_confirmation' => 'DifferentPassword@456',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('OldPassword@123', $this->customer->fresh()->password));
    }

    public function test_reset_session_must_be_started_before_a_password_can_be_changed(): void
    {
        $this->get(route('password.reset', ['token' => 'random-token']))
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('identifier');

        $this->post(route('password.update'), [
            'token' => 'random-token',
            'password' => 'NewPassword@456',
            'password_confirmation' => 'NewPassword@456',
        ])->assertSessionHasErrors('token');

        $this->assertTrue(Hash::check('OldPassword@123', $this->customer->fresh()->password));
    }

    public function test_expired_reset_session_is_rejected(): void
    {
        $this->post(route('password.email'), [
            'identifier' => 'brew.tester@example.com',
        ]);

        $token = session('password_reset.token');

        $this->travel(61)->minutes();

        $this->get(route('password.reset', ['token' => $token]))
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('identifier');
    }

    public function test_admin_can_reset_their_password_without_an_email_and_sign_in(): void
    {
        Notification::fake();

        $response = $this->post(route('admin.password.email'), [
            'identifier' => 'Brew Administrator',
        ]);

        $token = session('password_reset.token');

        $response->assertRedirect(route('admin.password.reset', ['token' => $token]));

        $this->get(route('admin.password.reset', ['token' => $token]))
            ->assertOk()
            ->assertViewIs('admin.auth.reset-password')
            ->assertSee('brew.admin@example.com');

        $this->post(route('admin.password.update'), [
            'token' => $token,
            'password' => 'AdminNew@456',
            'password_confirmation' => 'AdminNew@456',
        ])->assertRedirect(route('admin.login'));

        $this->assertTrue(Hash::check('AdminNew@456', $this->admin->fresh()->password));
        Notification::assertNothingSent();

        $this->post(route('admin.login.store'), [
            'email' => 'brew.admin@example.com',
            'password' => 'AdminNew@456',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_customer_accounts_cannot_be_reset_through_the_admin_flow(): void
    {
        $this->from(route('admin.password.request'))
            ->post(route('admin.password.email'), [
                'identifier' => 'brew.tester@example.com',
            ])
            ->assertRedirect(route('admin.password.request'))
            ->assertSessionHasErrors('identifier');

        $this->assertTrue(Hash::check('OldPassword@123', $this->customer->fresh()->password));
    }

    public function test_admin_accounts_cannot_be_reset_through_the_customer_flow(): void
    {
        $this->from(route('password.request'))
            ->post(route('password.email'), [
                'identifier' => 'brew.admin@example.com',
            ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('identifier');

        $this->assertTrue(Hash::check('AdminOld@123', $this->admin->fresh()->password));
    }
}

<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->createCustomer();
    }

    public function test_guests_cannot_view_audit_logs_or_the_live_feed(): void
    {
        $this->get(route('admin.audit.index'))->assertRedirect(route('admin.login'));
        $this->getJson(route('admin.audit.feed'))->assertUnauthorized();
    }

    public function test_customers_cannot_view_audit_logs_or_the_live_feed(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->get(route('admin.audit.index'))->assertForbidden();
        $this->actingAs($customer)->getJson(route('admin.audit.feed'))->assertForbidden();
    }

    public function test_admin_mutations_are_recorded_and_visible_in_the_console(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Audit Test Category',
            'description' => 'Category created to verify audit logging.',
            'is_active' => 1,
            'sort_order' => 7,
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'action' => 'Created category',
            'route_name' => 'admin.categories.store',
            'method' => 'POST',
            'path' => 'admin/categories',
            'status_code' => 302,
        ]);

        $this->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSee('Audit Logs')
            ->assertSee('Created category')
            ->assertSee('admin/categories');
    }

    public function test_live_feed_returns_only_newer_logs(): void
    {
        $admin = $this->admin();

        $first = $this->createAuditLog('Created category');
        $second = $this->createAuditLog('Updated product');

        $this->actingAs($admin)
            ->getJson(route('admin.audit.feed', ['after' => $first->id]))
            ->assertOk()
            ->assertJsonCount(1, 'logs')
            ->assertJsonPath('logs.0.id', $second->id)
            ->assertJsonPath('logs.0.action', 'Updated product')
            ->assertJsonPath('last_id', $second->id);
    }

    public function test_audit_context_never_stores_admin_passwords(): void
    {
        $admin = $this->admin();

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'Admin@12345',
        ])->assertRedirect(route('admin.dashboard'));

        $log = AuditLog::where('route_name', 'admin.login.store')->latest('id')->firstOrFail();

        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('Signed in', $log->action);
        $this->assertArrayNotHasKey('password', $log->context);
        $this->assertArrayNotHasKey('_token', $log->context);
        $this->assertSame($admin->email, $log->context['email']);
    }

    public function test_customer_actions_are_recorded_and_visible_to_admins(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->put('/profile', [
                'name' => 'Audited Customer',
                'email' => $customer->email,
                'phone' => '09181234567',
                'address' => '123 Audit Street',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $customer->id,
            'user_name' => 'Audited Customer',
            'actor_role' => 'customer',
            'action' => 'Updated profile',
            'route_name' => 'profile.update',
            'method' => 'PUT',
            'path' => 'profile',
            'status_code' => 302,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.audit.index', ['role' => 'customer']))
            ->assertOk()
            ->assertSee('Audited Customer')
            ->assertSee('Updated profile')
            ->assertSee('Customer');

        $this->actingAs($this->admin())
            ->getJson(route('admin.audit.feed', ['role' => 'customer']))
            ->assertOk()
            ->assertJsonFragment([
                'user_name' => 'Audited Customer',
                'actor_role' => 'customer',
                'action' => 'Updated profile',
            ]);
    }

    private function createAuditLog(string $action): AuditLog
    {
        $admin = $this->admin();

        return AuditLog::create([
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'action' => $action,
            'route_name' => 'admin.test',
            'method' => 'POST',
            'path' => 'admin/test',
            'status_code' => 302,
            'ip_address' => '127.0.0.1',
            'context' => [],
        ]);
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

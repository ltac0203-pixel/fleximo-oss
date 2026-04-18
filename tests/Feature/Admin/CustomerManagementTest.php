<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->customer()->create([
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_customer_list(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.customers.index'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page->component('Admin/Customers/Index')
                ->has('customers')
                ->has('statuses')
        );
    }

    public function test_customer_list_can_filter_by_status(): void
    {
        $suspendedCustomer = User::factory()->customer()->create([
            'is_active' => false,
        ]);
        $suspendedCustomer->forceFill(['account_status' => AccountStatus::Suspended])->save();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.customers.index', ['status' => 'suspended']));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page->component('Admin/Customers/Index')
                ->where('statusFilter', 'suspended')
        );
    }

    public function test_customer_list_can_search(): void
    {
        $targetCustomer = User::factory()->customer()->create([
            'name' => '検索対象ユーザー',
            'email' => 'target-search@example.com',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.customers.index', ['search' => '検索対象']));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page->component('Admin/Customers/Index')
                ->where('searchQuery', '検索対象')
        );
    }

    public function test_customer_list_can_sort(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.customers.index', [
                'sort' => 'name',
                'sort_dir' => 'asc',
            ]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page->component('Admin/Customers/Index')
                ->where('sortBy', 'name')
                ->where('sortDir', 'asc')
        );
    }

    public function test_admin_can_view_customer_detail(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.customers.show', $this->customer));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page->component('Admin/Customers/Show')
                ->has('customer')
                ->has('recentOrders')
        );
    }

    public function test_admin_can_view_customer_orders(): void
    {
        $tenant = Tenant::factory()->create();

        Order::factory()
            ->forTenant($tenant)
            ->forUser($this->customer)
            ->paid()
            ->count(3)
            ->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.customers.orders', $this->customer));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page->component('Admin/Customers/Orders')
                ->has('customer')
                ->has('orders')
                ->has('tenants')
        );
    }

    public function test_admin_can_suspend_customer(): void
    {
        $this->customer->createToken('test-token');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.customers.suspend', $this->customer), [
                'reason' => '不正利用の疑い',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertEquals(AccountStatus::Suspended, $this->customer->account_status);
        $this->assertFalse($this->customer->is_active);
        $this->assertEquals(0, $this->customer->tokens()->count());

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CustomerSuspended->value,
            'auditable_type' => User::class,
            'auditable_id' => $this->customer->id,
        ]);
    }

    public function test_admin_can_ban_customer(): void
    {
        $this->customer->createToken('test-token');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.customers.ban', $this->customer), [
                'reason' => '規約違反',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertEquals(AccountStatus::Banned, $this->customer->account_status);
        $this->assertFalse($this->customer->is_active);
        $this->assertEquals(0, $this->customer->tokens()->count());

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CustomerBanned->value,
            'auditable_type' => User::class,
            'auditable_id' => $this->customer->id,
        ]);
    }

    public function test_admin_can_reactivate_suspended_customer(): void
    {
        $this->customer->forceFill([
            'account_status' => AccountStatus::Suspended,
            'account_status_reason' => '不正利用の疑い',
            'is_active' => false,
        ])->save();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.customers.reactivate', $this->customer));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertEquals(AccountStatus::Active, $this->customer->account_status);
        $this->assertTrue($this->customer->is_active);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CustomerReactivated->value,
            'auditable_type' => User::class,
            'auditable_id' => $this->customer->id,
        ]);
    }

    public function test_admin_can_reactivate_banned_customer(): void
    {
        $this->customer->forceFill([
            'account_status' => AccountStatus::Banned,
            'account_status_reason' => '規約違反',
            'is_active' => false,
        ])->save();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.customers.reactivate', $this->customer));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertEquals(AccountStatus::Active, $this->customer->account_status);
        $this->assertTrue($this->customer->is_active);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CustomerReactivated->value,
            'auditable_type' => User::class,
            'auditable_id' => $this->customer->id,
        ]);
    }

    public function test_non_admin_cannot_access_customer_list(): void
    {
        $customerUser = User::factory()->customer()->create();

        $this->actingAs($customerUser)
            ->get(route('admin.customers.index'))
            ->assertForbidden();
    }

    public function test_cannot_suspend_non_customer_user(): void
    {
        $anotherAdmin = User::factory()->admin()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.customers.suspend', $anotherAdmin), [
                'reason' => 'テスト理由',
            ])
            ->assertForbidden();
    }

    public function test_cannot_reactivate_active_customer(): void
    {
        // account_status が Active の顧客は isAccountRestricted() が false なので Gate で拒否される
        $this->actingAs($this->admin)
            ->post(route('admin.customers.reactivate', $this->customer))
            ->assertForbidden();
    }

    public function test_suspend_requires_reason(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.customers.suspend', $this->customer), [
                // reason を送らない
            ]);

        $response->assertSessionHasErrors(['reason']);
    }
}

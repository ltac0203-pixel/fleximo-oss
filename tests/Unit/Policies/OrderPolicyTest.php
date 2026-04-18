<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Policies\OrderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPolicyTest extends TestCase
{
    use RefreshDatabase;

    private OrderPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new OrderPolicy;
    }

    public function test_view_any_and_create_allow_only_customer(): void
    {
        $customer = User::factory()->customer()->create();
        $staff = User::factory()->tenantStaff()->create();

        $this->assertTrue($this->policy->viewAny($customer));
        $this->assertTrue($this->policy->create($customer));
        $this->assertFalse($this->policy->viewAny($staff));
        $this->assertFalse($this->policy->create($staff));
    }

    public function test_customer_can_view_only_own_order(): void
    {
        $owner = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();
        $order = Order::factory()->forUser($owner)->create();

        $this->assertTrue($this->policy->view($owner, $order));
        $this->assertFalse($this->policy->view($otherCustomer, $order));
    }

    public function test_customer_cancel_requires_ownership_and_cancellable_status(): void
    {
        $owner = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();
        $tenantStaff = User::factory()->tenantStaff()->create();

        $cancellableOrder = Order::factory()->forUser($owner)->paid()->create();
        $completedOrder = Order::factory()->forUser($owner)->completed()->create();
        $otherUserOrder = Order::factory()->forUser($otherCustomer)->paid()->create();

        $this->assertTrue($this->policy->cancel($owner, $cancellableOrder));
        $this->assertFalse($this->policy->cancel($owner, $completedOrder));
        $this->assertFalse($this->policy->cancel($owner, $otherUserOrder));
        $this->assertFalse($this->policy->cancel($tenantStaff, $cancellableOrder));
    }

    public function test_tenant_methods_require_tenant_role_and_same_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = $this->createTenantUser($tenantA, UserRole::TenantAdmin);
        $staffA = $this->createTenantUser($tenantA, UserRole::TenantStaff);
        $adminB = $this->createTenantUser($tenantB, UserRole::TenantAdmin);
        $customer = User::factory()->customer()->create();

        $orderA = Order::factory()->forTenant($tenantA)->create();
        $cancellableOrderA = Order::factory()->forTenant($tenantA)->paid()->create();
        $completedOrderA = Order::factory()->forTenant($tenantA)->completed()->create();

        $this->assertTrue($this->policy->viewAnyForTenant($adminA));
        $this->assertTrue($this->policy->viewAnyForTenant($staffA));
        $this->assertFalse($this->policy->viewAnyForTenant($customer));

        foreach (['viewForTenant', 'updateStatus'] as $ability) {
            $this->assertTrue($this->policy->{$ability}($adminA, $orderA));
            $this->assertTrue($this->policy->{$ability}($staffA, $orderA));
            $this->assertFalse($this->policy->{$ability}($adminB, $orderA));
            $this->assertFalse($this->policy->{$ability}($customer, $orderA));
        }

        $this->assertTrue($this->policy->cancelForTenant($adminA, $cancellableOrderA));
        $this->assertTrue($this->policy->cancelForTenant($staffA, $cancellableOrderA));
        $this->assertFalse($this->policy->cancelForTenant($adminA, $completedOrderA));
        $this->assertFalse($this->policy->cancelForTenant($adminB, $cancellableOrderA));
        $this->assertFalse($this->policy->cancelForTenant($customer, $cancellableOrderA));
    }

    public function test_update_delete_and_admin_only_actions_are_always_denied(): void
    {
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->forUser($customer)->create();

        foreach (['update', 'delete', 'refund', 'restore', 'forceDelete'] as $ability) {
            $this->assertFalse($this->policy->{$ability}($customer, $order));
            $this->assertFalse($this->policy->{$ability}($admin, $order));
        }
    }

    private function createTenantUser(Tenant $tenant, UserRole $role): User
    {
        $user = User::factory()->create(['role' => $role]);

        $tenantRole = $role === UserRole::TenantAdmin
            ? TenantUserRole::Admin
            : TenantUserRole::Staff;

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => $tenantRole,
        ]);

        return $user->refresh();
    }
}

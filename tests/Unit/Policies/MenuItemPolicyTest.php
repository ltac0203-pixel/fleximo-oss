<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Policies\MenuItemPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuItemPolicyTest extends TestCase
{
    use RefreshDatabase;

    private MenuItemPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new MenuItemPolicy;
    }

    public function test_view_any_allows_only_tenant_roles(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = $this->createTenantUser($tenant, UserRole::TenantAdmin);
        $staff = $this->createTenantUser($tenant, UserRole::TenantStaff);
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->viewAny($staff));
        $this->assertFalse($this->policy->viewAny($customer));
    }

    public function test_view_requires_tenant_role_and_same_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = $this->createTenantUser($tenantA, UserRole::TenantAdmin);
        $staffA = $this->createTenantUser($tenantA, UserRole::TenantStaff);
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $itemA = MenuItem::factory()->create(['tenant_id' => $tenantA->id]);
        $itemB = MenuItem::factory()->create(['tenant_id' => $tenantB->id]);

        $this->assertTrue($this->policy->view($adminA, $itemA));
        $this->assertTrue($this->policy->view($staffA, $itemA));
        $this->assertFalse($this->policy->view($customer, $itemA));
        $this->assertFalse($this->policy->view($adminA, $itemB));
    }

    public function test_create_allows_tenant_admin_only(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = $this->createTenantUser($tenant, UserRole::TenantAdmin);
        $staff = $this->createTenantUser($tenant, UserRole::TenantStaff);
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($staff));
        $this->assertFalse($this->policy->create($customer));
    }

    public function test_update_related_methods_require_tenant_admin_and_same_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = $this->createTenantUser($tenantA, UserRole::TenantAdmin);
        $adminB = $this->createTenantUser($tenantB, UserRole::TenantAdmin);
        $staffA = $this->createTenantUser($tenantA, UserRole::TenantStaff);

        $itemA = MenuItem::factory()->create(['tenant_id' => $tenantA->id]);

        foreach (['update', 'delete', 'manageOptionGroups'] as $ability) {
            $this->assertTrue($this->policy->{$ability}($adminA, $itemA));
            $this->assertFalse($this->policy->{$ability}($staffA, $itemA));
            $this->assertFalse($this->policy->{$ability}($adminB, $itemA));
        }
    }

    public function test_toggle_sold_out_allows_tenant_roles_with_same_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = $this->createTenantUser($tenantA, UserRole::TenantAdmin);
        $staffA = $this->createTenantUser($tenantA, UserRole::TenantStaff);
        $adminB = $this->createTenantUser($tenantB, UserRole::TenantAdmin);
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $itemA = MenuItem::factory()->create(['tenant_id' => $tenantA->id]);

        $this->assertTrue($this->policy->toggleSoldOut($adminA, $itemA));
        $this->assertTrue($this->policy->toggleSoldOut($staffA, $itemA));
        $this->assertFalse($this->policy->toggleSoldOut($adminB, $itemA));
        $this->assertFalse($this->policy->toggleSoldOut($customer, $itemA));
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

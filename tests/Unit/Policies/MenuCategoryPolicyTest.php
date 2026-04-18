<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\MenuCategory;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Policies\MenuCategoryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuCategoryPolicyTest extends TestCase
{
    use RefreshDatabase;

    private MenuCategoryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new MenuCategoryPolicy;
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

        $categoryA = MenuCategory::factory()->create(['tenant_id' => $tenantA->id]);
        $categoryB = MenuCategory::factory()->create(['tenant_id' => $tenantB->id]);

        $this->assertTrue($this->policy->view($adminA, $categoryA));
        $this->assertTrue($this->policy->view($staffA, $categoryA));
        $this->assertFalse($this->policy->view($customer, $categoryA));
        $this->assertFalse($this->policy->view($adminA, $categoryB));
    }

    public function test_create_and_reorder_allow_tenant_admin_only(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = $this->createTenantUser($tenant, UserRole::TenantAdmin);
        $staff = $this->createTenantUser($tenant, UserRole::TenantStaff);
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->reorder($admin));

        $this->assertFalse($this->policy->create($staff));
        $this->assertFalse($this->policy->reorder($staff));

        $this->assertFalse($this->policy->create($customer));
        $this->assertFalse($this->policy->reorder($customer));
    }

    public function test_update_and_delete_require_tenant_admin_and_same_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = $this->createTenantUser($tenantA, UserRole::TenantAdmin);
        $adminB = $this->createTenantUser($tenantB, UserRole::TenantAdmin);
        $staffA = $this->createTenantUser($tenantA, UserRole::TenantStaff);

        $categoryA = MenuCategory::factory()->create(['tenant_id' => $tenantA->id]);

        foreach (['update', 'delete'] as $ability) {
            $this->assertTrue($this->policy->{$ability}($adminA, $categoryA));
            $this->assertFalse($this->policy->{$ability}($staffA, $categoryA));
            $this->assertFalse($this->policy->{$ability}($adminB, $categoryA));
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

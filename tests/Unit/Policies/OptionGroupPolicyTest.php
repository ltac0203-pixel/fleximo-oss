<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Policies\OptionGroupPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptionGroupPolicyTest extends TestCase
{
    use RefreshDatabase;

    private OptionGroupPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new OptionGroupPolicy;
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

        $groupA = OptionGroup::factory()->create(['tenant_id' => $tenantA->id]);
        $groupB = OptionGroup::factory()->create(['tenant_id' => $tenantB->id]);

        $this->assertTrue($this->policy->view($adminA, $groupA));
        $this->assertTrue($this->policy->view($staffA, $groupA));
        $this->assertFalse($this->policy->view($customer, $groupA));
        $this->assertFalse($this->policy->view($adminA, $groupB));
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

    public function test_update_and_delete_require_tenant_admin_and_same_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = $this->createTenantUser($tenantA, UserRole::TenantAdmin);
        $adminB = $this->createTenantUser($tenantB, UserRole::TenantAdmin);
        $staffA = $this->createTenantUser($tenantA, UserRole::TenantStaff);

        $groupA = OptionGroup::factory()->create(['tenant_id' => $tenantA->id]);

        foreach (['update', 'delete'] as $ability) {
            $this->assertTrue($this->policy->{$ability}($adminA, $groupA));
            $this->assertFalse($this->policy->{$ability}($staffA, $groupA));
            $this->assertFalse($this->policy->{$ability}($adminB, $groupA));
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

<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Policies\OptionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptionPolicyTest extends TestCase
{
    use RefreshDatabase;

    private OptionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new OptionPolicy;
    }

    public function test_view_any_allows_tenant_roles_with_same_tenant_option_group(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = $this->createTenantUser($tenantA, UserRole::TenantAdmin);
        $staffA = $this->createTenantUser($tenantA, UserRole::TenantStaff);
        $adminB = $this->createTenantUser($tenantB, UserRole::TenantAdmin);
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $groupA = OptionGroup::factory()->create(['tenant_id' => $tenantA->id]);

        $this->assertTrue($this->policy->viewAny($adminA, $groupA));
        $this->assertTrue($this->policy->viewAny($staffA, $groupA));
        $this->assertFalse($this->policy->viewAny($adminB, $groupA));
        $this->assertFalse($this->policy->viewAny($customer, $groupA));
    }

    public function test_create_allows_only_tenant_admin_with_same_tenant_option_group(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = $this->createTenantUser($tenantA, UserRole::TenantAdmin);
        $staffA = $this->createTenantUser($tenantA, UserRole::TenantStaff);
        $adminB = $this->createTenantUser($tenantB, UserRole::TenantAdmin);

        $groupA = OptionGroup::factory()->create(['tenant_id' => $tenantA->id]);

        $this->assertTrue($this->policy->create($adminA, $groupA));
        $this->assertFalse($this->policy->create($staffA, $groupA));
        $this->assertFalse($this->policy->create($adminB, $groupA));
    }

    public function test_update_and_delete_require_tenant_admin_and_same_tenant_option(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = $this->createTenantUser($tenantA, UserRole::TenantAdmin);
        $staffA = $this->createTenantUser($tenantA, UserRole::TenantStaff);
        $adminB = $this->createTenantUser($tenantB, UserRole::TenantAdmin);
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $groupA = OptionGroup::factory()->create(['tenant_id' => $tenantA->id]);
        $optionA = Option::factory()->create(['option_group_id' => $groupA->id]);

        foreach (['update', 'delete'] as $ability) {
            $this->assertTrue($this->policy->{$ability}($adminA, $optionA));
            $this->assertFalse($this->policy->{$ability}($staffA, $optionA));
            $this->assertFalse($this->policy->{$ability}($adminB, $optionA));
            $this->assertFalse($this->policy->{$ability}($customer, $optionA));
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

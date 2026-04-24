<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\TenantUserRole;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_be_created(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
        ]);

        $this->assertDatabaseHas('tenants', [
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
        ]);
    }

    public function test_tenant_slug_is_unique(): void
    {
        Tenant::factory()->create(['slug' => 'unique-slug']);

        $this->expectException(QueryException::class);
        Tenant::factory()->create(['slug' => 'unique-slug']);
    }

    public function test_tenant_is_active_returns_true_when_active(): void
    {
        $tenant = Tenant::factory()->create([
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->assertTrue($tenant->isActive());
    }

    public function test_tenant_is_active_returns_false_when_inactive(): void
    {
        $tenant = Tenant::factory()->create([
            'is_active' => false,
        ]);

        $this->assertFalse($tenant->isActive());
    }

    public function test_tenant_is_active_returns_false_when_suspended(): void
    {
        $tenant = Tenant::factory()->create([
            'is_active' => true,
            'status' => 'suspended',
        ]);

        $this->assertFalse($tenant->isActive());
    }

    public function test_active_scope_filters_inactive_tenants(): void
    {
        Tenant::factory()->create(['is_active' => true, 'status' => 'active']);
        Tenant::factory()->create(['is_active' => false, 'status' => 'active']);
        Tenant::factory()->create(['is_active' => true, 'status' => 'suspended']);

        $activeTenants = Tenant::active()->get();

        $this->assertCount(1, $activeTenants);
    }

    public function test_search_scope_filters_by_name(): void
    {
        Tenant::factory()->create(['name' => 'Cafe Sunshine']);
        Tenant::factory()->create(['name' => 'Restaurant Moon']);
        Tenant::factory()->create(['name' => 'Bakery Star']);

        $results = Tenant::search('Cafe')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Cafe Sunshine', $results->first()->name);
    }

    public function test_search_scope_filters_by_address(): void
    {
        Tenant::factory()->create(['name' => 'Restaurant A', 'address' => 'Tokyo Shibuya']);
        Tenant::factory()->create(['name' => 'Restaurant B', 'address' => 'Osaka Namba']);
        Tenant::factory()->create(['name' => 'Restaurant C', 'address' => 'Tokyo Shinjuku']);

        $results = Tenant::search('Tokyo')->get();

        $this->assertCount(2, $results);
    }

    public function test_search_scope_with_empty_keyword_returns_all(): void
    {
        Tenant::factory()->count(3)->create();

        $results = Tenant::search(null)->get();

        $this->assertCount(3, $results);
    }

    public function test_tenant_has_many_tenant_users(): void
    {
        $tenant = Tenant::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user1->id,
            'role' => TenantUserRole::Admin,
        ]);

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user2->id,
            'role' => TenantUserRole::Staff,
        ]);

        $this->assertCount(2, $tenant->tenantUsers);
    }

    public function test_tenant_admins_returns_only_admin_users(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create();
        $staff = User::factory()->create();

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'role' => TenantUserRole::Admin,
        ]);

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'role' => TenantUserRole::Staff,
        ]);

        $admins = $tenant->admins;

        $this->assertCount(1, $admins);
        $this->assertEquals($admin->id, $admins->first()->id);
    }

    public function test_tenant_staff_returns_only_staff_users(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create();
        $staff = User::factory()->create();

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'role' => TenantUserRole::Admin,
        ]);

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'role' => TenantUserRole::Staff,
        ]);

        $staffUsers = $tenant->staff;

        $this->assertCount(1, $staffUsers);
        $this->assertEquals($staff->id, $staffUsers->first()->id);
    }

    public function test_tenant_all_staff_returns_both_admins_and_staff(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create();
        $staff = User::factory()->create();

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'role' => TenantUserRole::Admin,
        ]);

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'role' => TenantUserRole::Staff,
        ]);

        $allStaff = $tenant->allStaff;

        $this->assertCount(2, $allStaff);
        $this->assertTrue($allStaff->contains('id', $admin->id));
        $this->assertTrue($allStaff->contains('id', $staff->id));
    }
}

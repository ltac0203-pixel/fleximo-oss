<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\TenantUserRole;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();
    }

    public function test_tenant_user_can_be_created(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin()->create();

        $tenantUser = TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => TenantUserRole::Admin,
        ]);

        $this->assertDatabaseHas('tenant_users', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
    }

    public function test_tenant_user_unique_constraint(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin()->create();

        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_is_admin_returns_true_for_admin(): void
    {
        $tenantUser = TenantUser::factory()->admin()->create();

        $this->assertTrue($tenantUser->isAdmin());
        $this->assertFalse($tenantUser->isStaff());
    }

    public function test_is_staff_returns_true_for_staff(): void
    {
        $tenantUser = TenantUser::factory()->staff()->create();

        $this->assertFalse($tenantUser->isAdmin());
        $this->assertTrue($tenantUser->isStaff());
    }

    public function test_admins_scope_filters_admins(): void
    {
        $tenant = Tenant::factory()->create();

        TenantUser::factory()->admin()->create(['tenant_id' => $tenant->id]);
        TenantUser::factory()->staff()->create(['tenant_id' => $tenant->id]);

        app(TenantContext::class)->setTenant($tenant->id);

        $admins = TenantUser::admins()->get();

        $this->assertCount(1, $admins);
        $this->assertTrue($admins->first()->isAdmin());
    }

    public function test_staff_scope_filters_staff(): void
    {
        $tenant = Tenant::factory()->create();

        TenantUser::factory()->admin()->create(['tenant_id' => $tenant->id]);
        TenantUser::factory()->staff()->create(['tenant_id' => $tenant->id]);

        app(TenantContext::class)->setTenant($tenant->id);

        $staff = TenantUser::staff()->get();

        $this->assertCount(1, $staff);
        $this->assertTrue($staff->first()->isStaff());
    }

    public function test_tenant_user_belongs_to_user(): void
    {
        $tenantUser = TenantUser::factory()->create();

        $this->assertInstanceOf(User::class, $tenantUser->user);
    }

    public function test_tenant_user_belongs_to_tenant(): void
    {
        $tenantUser = TenantUser::factory()->create();

        $this->assertInstanceOf(Tenant::class, $tenantUser->tenant);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(TenantContext::class)->clear();
    }

    public function test_tenant_scope_filters_by_tenant_id(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $user1 = User::factory()->tenantAdmin()->create();
        $user2 = User::factory()->tenantAdmin()->create();

        TenantUser::factory()->create(['tenant_id' => $tenant1->id, 'user_id' => $user1->id]);
        TenantUser::factory()->create(['tenant_id' => $tenant2->id, 'user_id' => $user2->id]);

        app(TenantContext::class)->setTenant($tenant1->id);

        $tenantUsers = TenantUser::all();

        $this->assertCount(1, $tenantUsers);
        $this->assertEquals($tenant1->id, $tenantUsers->first()->tenant_id);
    }

    public function test_without_tenant_scope_returns_all_records(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $user1 = User::factory()->tenantAdmin()->create();
        $user2 = User::factory()->tenantAdmin()->create();

        TenantUser::factory()->create(['tenant_id' => $tenant1->id, 'user_id' => $user1->id]);
        TenantUser::factory()->create(['tenant_id' => $tenant2->id, 'user_id' => $user2->id]);

        app(TenantContext::class)->setTenant($tenant1->id);

        $allTenantUsers = TenantUser::withoutTenantScope()->get();

        $this->assertCount(2, $allTenantUsers);
    }

    public function test_no_tenant_context_returns_all_records(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $user1 = User::factory()->tenantAdmin()->create();
        $user2 = User::factory()->tenantAdmin()->create();

        TenantUser::factory()->create(['tenant_id' => $tenant1->id, 'user_id' => $user1->id]);
        TenantUser::factory()->create(['tenant_id' => $tenant2->id, 'user_id' => $user2->id]);

        $tenantUsers = TenantUser::all();

        $this->assertCount(2, $tenantUsers);
    }
}

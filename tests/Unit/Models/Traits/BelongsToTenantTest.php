<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Traits;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BelongsToTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(TenantContext::class)->clear();
    }

    public function test_tenant_id_is_automatically_set_on_creating(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin()->create();

        app(TenantContext::class)->setTenant($tenant->id);

        $tenantUser = TenantUser::create([
            'user_id' => $user->id,
            'role' => 'admin',
        ]);

        $this->assertEquals($tenant->id, $tenantUser->tenant_id);
    }

    public function test_tenant_id_is_not_overwritten_if_already_set(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin()->create();

        app(TenantContext::class)->setTenant($tenant1->id);

        $tenantUser = TenantUser::create([
            'tenant_id' => $tenant2->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);

        $this->assertEquals($tenant2->id, $tenantUser->tenant_id);
    }

    public function test_model_has_tenant_relationship(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin()->create();

        app(TenantContext::class)->setTenant($tenant->id);

        $tenantUser = TenantUser::create([
            'user_id' => $user->id,
            'role' => 'admin',
        ]);

        $this->assertInstanceOf(Tenant::class, $tenantUser->tenant);
        $this->assertEquals($tenant->id, $tenantUser->tenant->id);
    }
}

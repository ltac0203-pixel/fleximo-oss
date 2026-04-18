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

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();
    }

    public function test_user_has_one_tenant_user(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $tenantUser = TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $this->assertNotNull($user->tenantUser);
        $this->assertEquals($tenantUser->id, $user->tenantUser->id);
    }

    public function test_user_has_one_through_tenant(): void
    {
        $user = User::factory()->tenantAdmin()->create();
        $tenant = Tenant::factory()->create();

        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin,
        ]);
        $user->refresh();

        $this->assertNotNull($user->getTenant());
        $this->assertEquals($tenant->id, $user->getTenant()->id);
    }

    public function test_user_is_admin(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isTenantAdmin());
        $this->assertFalse($user->isTenantStaff());
        $this->assertFalse($user->isCustomer());
    }

    public function test_user_is_tenant_admin(): void
    {
        $user = User::factory()->tenantAdmin()->create();

        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isTenantAdmin());
        $this->assertFalse($user->isTenantStaff());
        $this->assertFalse($user->isCustomer());
    }

    public function test_user_is_tenant_staff(): void
    {
        $user = User::factory()->tenantStaff()->create();

        $this->assertFalse($user->isTenantAdmin());
        $this->assertTrue($user->isTenantStaff());
        $this->assertFalse($user->isCustomer());
    }

    public function test_user_is_customer(): void
    {
        $user = User::factory()->customer()->create();

        $this->assertFalse($user->isTenantAdmin());
        $this->assertFalse($user->isTenantStaff());
        $this->assertTrue($user->isCustomer());
    }

    public function test_has_tenant_role_for_tenant_admin(): void
    {
        $user = User::factory()->tenantAdmin()->create();

        $this->assertTrue($user->hasTenantRole());
    }

    public function test_has_tenant_role_for_tenant_staff(): void
    {
        $user = User::factory()->tenantStaff()->create();

        $this->assertTrue($user->hasTenantRole());
    }

    public function test_has_tenant_role_returns_false_for_customer(): void
    {
        $user = User::factory()->customer()->create();

        $this->assertFalse($user->hasTenantRole());
    }

    public function test_get_tenant_id_returns_tenant_id_for_tenant_user(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin()->create();
        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals($tenant->id, $user->getTenantId());
    }

    public function test_get_tenant_id_returns_null_for_customer(): void
    {
        $user = User::factory()->customer()->create();

        $this->assertNull($user->getTenantId());
    }

    public function test_get_tenant_returns_tenant_for_tenant_user(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin()->create();
        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $user->getTenant());
        $this->assertEquals($tenant->id, $user->getTenant()->id);
    }

    public function test_get_tenant_id_resolves_assignment_when_cached_relation_is_null_and_context_is_not_set(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin()->create();
        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        // コンテキスト未設定時にスコープ付き取得が null をキャッシュした状態を再現
        $user->setRelation('tenantUser', null);
        app(TenantContext::class)->clear();

        $this->assertEquals($tenant->id, $user->getTenantId());
    }

    public function test_get_tenant_resolves_assignment_when_cached_relation_is_null_and_context_is_not_set(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin()->create();
        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        // コンテキスト未設定時にスコープ付き取得が null をキャッシュした状態を再現
        $user->setRelation('tenantUser', null);
        app(TenantContext::class)->clear();

        $resolvedTenant = $user->getTenant();

        $this->assertInstanceOf(Tenant::class, $resolvedTenant);
        $this->assertEquals($tenant->id, $resolvedTenant->id);
    }

    public function test_get_tenant_returns_null_for_customer(): void
    {
        $user = User::factory()->customer()->create();

        $this->assertNull($user->getTenant());
    }

    public function test_is_active_returns_true_for_active_user(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->assertTrue($user->isActive());
    }

    public function test_is_active_returns_false_for_inactive_user(): void
    {
        $user = User::factory()->inactive()->create();

        $this->assertFalse($user->isActive());
    }

    public function test_set_tenant_context_sets_tenant_for_tenant_user(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin()->create();
        TenantUser::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);
        $context = app(TenantContext::class);

        $user->setTenantContext();

        $this->assertEquals($tenant->id, $context->getTenantId());
    }

    public function test_role_label_returns_japanese_name(): void
    {
        $user = User::factory()->tenantAdmin()->create();

        $this->assertEquals('テナント管理者', $user->role->label());
    }
}

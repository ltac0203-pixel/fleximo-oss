<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\TenantUserRole;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantAdmin(): array
    {
        $tenant = Tenant::factory()->create([
            'name' => 'テストテナント',
            'email' => 'test@example.com',
            'phone' => '03-1234-5678',
        ]);

        $admin = User::factory()->tenantAdmin()->create();
        TenantUser::factory()->create([
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin,
        ]);
        $admin->refresh();

        return [$tenant, $admin];
    }

    private function createTenantStaff(Tenant $tenant): User
    {
        $staff = User::factory()->tenantStaff()->create();
        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Staff,
        ]);
        $staff->refresh();

        return $staff;
    }

    public function test_tenant_admin_can_view_profile(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();

        $response = $this->actingAs($admin)
            ->getJson('/api/tenant/profile');

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'slug',
                'address',
                'today_business_hours',
                'is_open',
                'email',
                'phone',
                'business_hours',
            ]);

        $this->assertEquals('test@example.com', $response->json('email'));
        $this->assertEquals('03-1234-5678', $response->json('phone'));
    }

    public function test_tenant_staff_can_view_profile(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();
        $staff = $this->createTenantStaff($tenant);

        $response = $this->actingAs($staff)
            ->getJson('/api/tenant/profile');

        $response->assertOk()
            ->assertJson([
                'id' => $tenant->id,
                'name' => $tenant->name,
            ]);
    }

    public function test_tenant_admin_can_update_profile(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();

        $response = $this->actingAs($admin)
            ->patchJson('/api/tenant/profile', [
                'name' => '新しい店名',
                'email' => 'new@example.com',
            ]);

        $response->assertOk()
            ->assertJson([
                'name' => '新しい店名',
                'email' => 'new@example.com',
            ]);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => '新しい店名',
            'email' => 'new@example.com',
        ]);
    }

    public function test_tenant_staff_cannot_update_profile(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();
        $staff = $this->createTenantStaff($tenant);

        $response = $this->actingAs($staff)
            ->patchJson('/api/tenant/profile', [
                'name' => '変更されないはず',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'テストテナント',
        ]);
    }

    public function test_customer_cannot_access_profile(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)
            ->getJson('/api/tenant/profile');

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/tenant/profile');

        $response->assertUnauthorized();
    }

    public function test_profile_update_invalidates_cache(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();

        $cacheKey = "tenant:{$tenant->id}:profile";
        Cache::put($cacheKey, 'cached_value', 3600);

        $this->actingAs($admin)
            ->patchJson('/api/tenant/profile', [
                'name' => '新しい店名',
            ]);

        $this->assertNull(Cache::get($cacheKey));
    }

    public function test_profile_update_records_audit_log(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();

        $this->actingAs($admin)
            ->patchJson('/api/tenant/profile', [
                'name' => '新しい店名',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'action' => 'tenant.updated',
            'auditable_type' => Tenant::class,
            'auditable_id' => $tenant->id,
        ]);

        $auditLog = AuditLog::where('action', 'tenant.updated')->first();

        $this->assertEquals('テストテナント', $auditLog->old_values['name']);
        $this->assertEquals('新しい店名', $auditLog->new_values['name']);
    }

    public function test_profile_update_validation_errors(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();

        $response = $this->actingAs($admin)
            ->patchJson('/api/tenant/profile', [
                'email' => 'invalid-email',
                'business_hours' => [
                    ['weekday' => 1, 'open_time' => 'not-a-time', 'close_time' => '21:00'],
                ],
            ]);

        $response->assertUnprocessable();
    }

    public function test_partial_update_only_changes_specified_fields(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();

        $response = $this->actingAs($admin)
            ->patchJson('/api/tenant/profile', [
                'name' => '新しい店名',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => '新しい店名',
            'email' => 'test@example.com',
        ]);
    }

    public function test_empty_update_returns_current_profile(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();

        $response = $this->actingAs($admin)
            ->patchJson('/api/tenant/profile', []);

        $response->assertOk()
            ->assertJson([
                'name' => 'テストテナント',
            ]);
    }

    public function test_business_hours_update(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();

        $response = $this->actingAs($admin)
            ->patchJson('/api/tenant/profile', [
                'business_hours' => [
                    ['weekday' => 1, 'open_time' => '09:00', 'close_time' => '21:00'],
                    ['weekday' => 2, 'open_time' => '09:00', 'close_time' => '21:00'],
                ],
            ]);

        $response->assertOk();
    }
}

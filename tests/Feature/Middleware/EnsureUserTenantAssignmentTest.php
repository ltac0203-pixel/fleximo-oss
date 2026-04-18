<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureUserTenantAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用エンドポイント
        Route::middleware(['auth:sanctum', 'tenant.user-assigned'])
            ->get('/test-tenant-exists/endpoint', function () {
                return response()->json(['success' => true]);
            });
    }

    public function test_tenant_admin_with_tenant_passes(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin()->create();
        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/test-tenant-exists/endpoint');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_tenant_staff_with_tenant_passes(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantStaff()->create();
        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/test-tenant-exists/endpoint');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_user_without_tenant_gets_403(): void
    {
        $user = User::factory()->tenantAdmin()->create();

        $response = $this->actingAs($user)
            ->getJson('/test-tenant-exists/endpoint');

        $response->assertForbidden();
    }

    public function test_customer_without_tenant_gets_403(): void
    {
        $user = User::factory()->customer()->create();

        $response = $this->actingAs($user)
            ->getJson('/test-tenant-exists/endpoint');

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_gets_401(): void
    {
        $this->getJson('/test-tenant-exists/endpoint')
            ->assertUnauthorized();
    }
}

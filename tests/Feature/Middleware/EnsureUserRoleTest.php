<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureUserRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'role:tenant_admin'])->get('/admin-only', function () {
            return 'Admin Only';
        });

        Route::middleware(['web', 'role:tenant_admin,tenant_staff'])->get('/tenant-only', function () {
            return 'Tenant Only';
        });

        Route::middleware(['web', 'role:customer'])->get('/customer-only', function () {
            return 'Customer Only';
        });
    }

    public function test_tenant_admin_can_access_admin_only_route(): void
    {
        $user = User::factory()->tenantAdmin()->create();

        $response = $this->actingAs($user)->get('/admin-only');

        $response->assertOk();
        $response->assertSee('Admin Only');
    }

    public function test_tenant_staff_cannot_access_admin_only_route(): void
    {
        $user = User::factory()->tenantStaff()->create();

        $response = $this->actingAs($user)->get('/admin-only');

        $response->assertForbidden();
    }

    public function test_tenant_admin_can_access_tenant_only_route(): void
    {
        $user = User::factory()->tenantAdmin()->create();

        $response = $this->actingAs($user)->get('/tenant-only');

        $response->assertOk();
        $response->assertSee('Tenant Only');
    }

    public function test_tenant_staff_can_access_tenant_only_route(): void
    {
        $user = User::factory()->tenantStaff()->create();

        $response = $this->actingAs($user)->get('/tenant-only');

        $response->assertOk();
        $response->assertSee('Tenant Only');
    }

    public function test_customer_cannot_access_tenant_only_route(): void
    {
        $user = User::factory()->customer()->create();

        $response = $this->actingAs($user)->get('/tenant-only');

        $response->assertForbidden();
    }

    public function test_customer_can_access_customer_only_route(): void
    {
        $user = User::factory()->customer()->create();

        $response = $this->actingAs($user)->get('/customer-only');

        $response->assertOk();
        $response->assertSee('Customer Only');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/admin-only');

        $response->assertRedirect(route('login'));
    }

    public function test_invalid_role_configuration_returns_500_error(): void
    {
        Route::middleware(['web', 'role:invalid_role'])->get('/invalid-role-route', function () {
            return 'Should not reach here';
        });

        $user = User::factory()->tenantAdmin()->create();

        $response = $this->actingAs($user)->get('/invalid-role-route');

        $response->assertStatus(500);
    }

    public function test_user_with_null_role_is_denied_access(): void
    {
        $user = User::factory()->tenantAdmin()->create();
        // roleをnullに設定（DBには保存せず、メモリ上でテスト）
        // DBにはNOT NULL制約があるが、防御的コードとしてnullも処理できることを確認
        $user->role = null;

        $response = $this->actingAs($user)->get('/admin-only');

        $response->assertForbidden();
    }

    public function test_mixed_valid_and_invalid_roles_only_uses_valid_roles(): void
    {
        Route::middleware(['web', 'role:tenant_admin,invalid_role'])->get('/mixed-roles', function () {
            return 'Mixed Roles Route';
        });

        $user = User::factory()->tenantAdmin()->create();

        $response = $this->actingAs($user)->get('/mixed-roles');

        $response->assertOk();
        $response->assertSee('Mixed Roles Route');
    }
}

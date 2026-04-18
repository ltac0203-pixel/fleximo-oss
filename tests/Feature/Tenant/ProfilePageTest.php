<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    private User $tenantAdmin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
            'address' => 'Tokyo',
            'email' => 'test@example.com',
            'phone' => '03-1234-5678',
        ]);

        $this->tenantAdmin = User::factory()->create([
            'role' => UserRole::TenantAdmin,
        ]);

        TenantUser::factory()->create([
            'user_id' => $this->tenantAdmin->id,
            'tenant_id' => $this->tenant->id,
            'role' => \App\Enums\TenantUserRole::Admin,
        ]);
        $this->tenantAdmin->refresh();
    }

    public function test_tenant_admin_can_view_profile(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.profile.index'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Profile/Index')
                ->has('tenant')
        );
    }

    public function test_tenant_admin_can_view_profile_edit_page(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.profile.edit'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Profile/Edit')
                ->has('tenant')
        );
    }

    public function test_tenant_admin_can_update_profile(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->patch(route('tenant.profile.update'), [
                'name' => 'Updated Restaurant',
                'address' => 'Osaka',
                'email' => 'updated@example.com',
                'phone' => '06-9876-5432',
            ]);

        $response->assertRedirect(route('tenant.profile.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'name' => 'Updated Restaurant',
            'address' => 'Osaka',
        ]);
    }

    public function test_customer_cannot_access_tenant_admin_pages(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)
            ->get(route('tenant.dashboard'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('tenant.profile.index'))
            ->assertForbidden();
    }

    public function test_tenant_staff_can_access_dashboard_and_profile(): void
    {
        $staff = User::factory()->create(['role' => UserRole::TenantStaff]);

        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => \App\Enums\TenantUserRole::Staff,
        ]);
        $staff->refresh();

        // スタッフはダッシュボードを閲覧可能
        $this->actingAs($staff)
            ->get(route('tenant.dashboard'))
            ->assertOk();

        // スタッフは店舗設定を閲覧可能
        $this->actingAs($staff)
            ->get(route('tenant.profile.index'))
            ->assertOk();

        // スタッフは店舗設定編集画面にはアクセス不可
        $this->actingAs($staff)
            ->get(route('tenant.profile.edit'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('tenant.profile.index'))
            ->assertRedirect(route('login'));
    }
}

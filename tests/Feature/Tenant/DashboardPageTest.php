<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    private User $tenantAdmin;

    private User $tenantStaff;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->clear();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
        ]);

        $this->tenantAdmin = User::factory()->create([
            'role' => UserRole::TenantAdmin,
        ]);

        TenantUser::factory()->create([
            'user_id' => $this->tenantAdmin->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Admin,
        ]);
        $this->tenantAdmin->refresh();

        $this->tenantStaff = User::factory()->create([
            'role' => UserRole::TenantStaff,
        ]);

        TenantUser::factory()->create([
            'user_id' => $this->tenantStaff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);
        $this->tenantStaff->refresh();
    }

    public function test_tenant_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.dashboard'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Dashboard/Index')
                ->has('tenant')
                ->has('summary')
                ->has('recentSales')
        );
    }

    public function test_tenant_staff_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->tenantStaff)
            ->get(route('tenant.dashboard'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Tenant/Dashboard/Index')
                ->has('tenant')
                ->has('summary')
        );
    }

    public function test_customer_cannot_access_dashboard(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)
            ->get(route('tenant.dashboard'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('tenant.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_summary_has_required_structure(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.dashboard'));

        $response->assertOk();

        $props = $response->viewData('page')['props'];
        $summary = $props['summary'];

        $summaryData = isset($summary['data']) ? $summary['data'] : $summary;

        $this->assertArrayHasKey('today', $summaryData);
        $this->assertArrayHasKey('yesterday', $summaryData);
        $this->assertArrayHasKey('this_month', $summaryData);
        $this->assertArrayHasKey('last_month', $summaryData);
        $this->assertArrayHasKey('comparison', $summaryData);

        $this->assertArrayHasKey('sales', $summaryData['today']);
        $this->assertArrayHasKey('orders', $summaryData['today']);
        $this->assertArrayHasKey('average', $summaryData['today']);

        $this->assertArrayHasKey('daily_change', $summaryData['comparison']);
        $this->assertArrayHasKey('monthly_change', $summaryData['comparison']);
        $this->assertArrayHasKey('sales_percent', $summaryData['comparison']['daily_change']);
        $this->assertArrayHasKey('orders_percent', $summaryData['comparison']['daily_change']);
    }

    public function test_recent_sales_is_array(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.dashboard'));

        $response->assertOk();

        $props = $response->viewData('page')['props'];
        $this->assertIsArray($props['recentSales']);
    }

    public function test_unapproved_tenant_admin_can_access_dashboard(): void
    {
        $unapprovedTenant = Tenant::factory()->create([
            'is_approved' => false,
        ]);

        $unapprovedAdmin = User::factory()->create([
            'role' => UserRole::TenantAdmin,
        ]);

        TenantUser::factory()->create([
            'user_id' => $unapprovedAdmin->id,
            'tenant_id' => $unapprovedTenant->id,
            'role' => TenantUserRole::Admin,
        ]);
        $unapprovedAdmin->refresh();

        $response = $this->actingAs($unapprovedAdmin)
            ->get(route('tenant.dashboard'));

        // tenant.user-approved ミドルウェアの allowedRoutes に 'tenant.dashboard' が含まれるため通過する
        $response->assertOk();
    }

    public function test_unapproved_tenant_is_redirected_from_other_routes(): void
    {
        $unapprovedTenant = Tenant::factory()->create([
            'is_approved' => false,
        ]);

        $unapprovedAdmin = User::factory()->create([
            'role' => UserRole::TenantAdmin,
            'email_verified_at' => now(),
        ]);

        TenantUser::factory()->create([
            'user_id' => $unapprovedAdmin->id,
            'tenant_id' => $unapprovedTenant->id,
            'role' => TenantUserRole::Admin,
        ]);
        $unapprovedAdmin->refresh();

        // tenant.user-approved グループ内の承認必須ルートにアクセスすると、ダッシュボードにリダイレクトされる
        $response = $this->actingAs($unapprovedAdmin)
            ->get(route('tenant.profile.index'));

        $response->assertRedirect(route('tenant.dashboard'));
    }
}

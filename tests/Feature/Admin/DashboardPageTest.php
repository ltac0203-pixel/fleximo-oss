<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
    }

    public function test_admin_can_access_dashboard_with_revenue_props(): void
    {
        $date = Carbon::today()->subDay();
        $tenantA = Tenant::factory()->create([
            'name' => 'Tenant A',
            'platform_fee_rate_bps' => 500,
        ]);
        $tenantB = Tenant::factory()->create([
            'name' => 'Tenant B',
            'platform_fee_rate_bps' => null,
        ]);
        $customer = User::factory()->customer()->create();

        Order::factory()
            ->forTenant($tenantA)
            ->forUser($customer)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(1000)
            ->create();
        Order::factory()
            ->forTenant($tenantB)
            ->forUser($customer)
            ->forBusinessDate($date->toDateString())
            ->completed()
            ->totalAmount(2000)
            ->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Dashboard')
                ->has('stats')
                ->has('revenueDashboard.overview')
                ->has('revenueDashboard.trend')
                ->has('revenueDashboard.ranking')
                ->where('revenueDashboard.overview.gmv_total', 3000)
                ->where('revenueDashboard.overview.estimated_fee_total', 170)
                ->where('revenueDashboard.overview.active_tenant_count', 2)
        );
    }

    public function test_non_admin_cannot_access_dashboard(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_accepts_date_filters_and_ranking_limit(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard', [
                'start_date' => '2026-02-01',
                'end_date' => '2026-02-10',
                'ranking_limit' => 5,
            ]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Dashboard')
                ->where('revenueFilters.start_date', '2026-02-01')
                ->where('revenueFilters.end_date', '2026-02-10')
                ->where('revenueFilters.ranking_limit', 5)
        );
    }

    public function test_dashboard_rejects_invalid_date_range(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.dashboard'))
            ->get(route('admin.dashboard', [
                'start_date' => '2026-03-01',
                'end_date' => '2026-02-01',
            ]));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHasErrors(['end_date']);
    }

    public function test_dashboard_rejects_too_long_date_range(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.dashboard'))
            ->get(route('admin.dashboard', [
                'start_date' => '2025-01-01',
                'end_date' => '2026-03-01',
            ]));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHasErrors(['end_date']);
    }
}

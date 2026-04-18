<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\MetricType;
use App\Models\AnalyticsCache;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlatformRevenueDashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformRevenueDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlatformRevenueDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlatformRevenueDashboardService;
    }

    public function test_build_dashboard_uses_cached_platform_daily_sales_when_available(): void
    {
        $date = Carbon::create(2026, 2, 1);
        $tenantA = Tenant::factory()->create([
            'name' => 'Tenant A',
            'platform_fee_rate_bps' => 500,
        ]);
        $tenantB = Tenant::factory()->create([
            'name' => 'Tenant B',
            'platform_fee_rate_bps' => null,
        ]);

        AnalyticsCache::saveCache(
            null,
            MetricType::DailySales,
            $date,
            [
                'total_sales' => 3000,
                'order_count' => 2,
                'average_order_value' => 1500,
                'tenant_breakdown' => [
                    ['tenant_id' => $tenantA->id, 'sales' => 1000, 'count' => 1],
                    ['tenant_id' => $tenantB->id, 'sales' => 2000, 'count' => 1],
                ],
            ]
        );

        $result = $this->service->buildDashboard($date, $date, 10);

        $this->assertSame(3000, $result['overview']['gmv_total']);
        $this->assertSame(2, $result['overview']['order_count_total']);
        $this->assertSame(1500, $result['overview']['avg_order_value']);
        $this->assertSame(170, $result['overview']['estimated_fee_total']);
        $this->assertSame(2, $result['overview']['active_tenant_count']);
        $this->assertCount(2, $result['ranking']);
        $this->assertSame($tenantB->id, $result['ranking'][0]['tenant_id']);
        $this->assertSame(600, $result['ranking'][0]['fee_rate_bps']);
        $this->assertSame(500, $result['ranking'][1]['fee_rate_bps']);
        $this->assertSame(170, $result['trend'][0]['estimated_fee']);
    }

    public function test_build_dashboard_falls_back_to_orders_when_cache_is_missing(): void
    {
        $date = Carbon::create(2026, 2, 2);
        $tenantA = Tenant::factory()->create([
            'name' => 'Tenant A',
            'platform_fee_rate_bps' => 700,
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

        Order::factory()
            ->forTenant($tenantB)
            ->forUser($customer)
            ->forBusinessDate($date->toDateString())
            ->pendingPayment()
            ->totalAmount(5000)
            ->create();

        $result = $this->service->buildDashboard($date, $date, 10);

        $this->assertSame(3000, $result['overview']['gmv_total']);
        $this->assertSame(2, $result['overview']['order_count_total']);
        $this->assertSame(1500, $result['overview']['avg_order_value']);
        $this->assertSame(190, $result['overview']['estimated_fee_total']);
        $this->assertSame(190, $result['trend'][0]['estimated_fee']);
    }

    public function test_build_dashboard_supports_hybrid_cache_and_direct_aggregation(): void
    {
        $day1 = Carbon::create(2026, 2, 3);
        $day2 = Carbon::create(2026, 2, 4);
        $tenantA = Tenant::factory()->create([
            'name' => 'Tenant A',
            'platform_fee_rate_bps' => 500,
        ]);
        $tenantB = Tenant::factory()->create([
            'name' => 'Tenant B',
            'platform_fee_rate_bps' => 800,
        ]);
        $customer = User::factory()->customer()->create();

        AnalyticsCache::saveCache(
            null,
            MetricType::DailySales,
            $day1,
            [
                'total_sales' => 1000,
                'order_count' => 1,
                'average_order_value' => 1000,
                'tenant_breakdown' => [
                    ['tenant_id' => $tenantA->id, 'sales' => 1000, 'count' => 1],
                ],
            ]
        );

        Order::factory()
            ->forTenant($tenantA)
            ->forUser($customer)
            ->forBusinessDate($day2->toDateString())
            ->paid()
            ->totalAmount(500)
            ->create();

        Order::factory()
            ->forTenant($tenantB)
            ->forUser($customer)
            ->forBusinessDate($day2->toDateString())
            ->accepted()
            ->totalAmount(1500)
            ->create();

        $result = $this->service->buildDashboard($day1, $day2, 10);

        $this->assertSame(3000, $result['overview']['gmv_total']);
        $this->assertSame(3, $result['overview']['order_count_total']);
        $this->assertSame(195, $result['overview']['estimated_fee_total']);
        $this->assertCount(2, $result['trend']);
        $this->assertSame(50, $result['trend'][0]['estimated_fee']);
        $this->assertSame(145, $result['trend'][1]['estimated_fee']);
        $this->assertCount(2, $result['ranking']);
        $this->assertSame($tenantA->id, $result['ranking'][0]['tenant_id']);
        $this->assertSame(1500, $result['ranking'][0]['gmv']);
        $this->assertSame(75, $result['ranking'][0]['estimated_fee']);
        $this->assertSame(50.0, $result['ranking'][0]['share_percent']);
        $this->assertSame($tenantB->id, $result['ranking'][1]['tenant_id']);
        $this->assertSame(120, $result['ranking'][1]['estimated_fee']);
    }
}

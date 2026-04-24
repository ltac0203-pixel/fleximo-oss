<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\SalesPeriod;
use App\Services\Dashboard\StatsCacheResolver;
use App\Services\TenantDashboardService;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

// Phase 2 PR2 の end-to-end acceptance gate:
// TenantDashboardService の 7 public メソッドが、リファクタ前後で完全に同じ
// キャッシュキー文字列 + TTL で Cache::remember を呼ぶことをハードコード比較で検証する。
// 1 文字でも崩れると本番キャッシュが全空振りするので、期待値はすべて生文字列で直書き。
class CacheKeyParityTest extends TestCase
{
    use RefreshDatabase;

    private TenantDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-04-24 12:00:00'));
        Cache::spy();
        $this->service = app(TenantDashboardService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_get_summary_passes_expected_key_and_ttl_for_today(): void
    {
        $this->service->getSummary(1, Carbon::parse('2026-04-24'));

        Cache::shouldHaveReceived('remember')
            ->once()
            ->with('tenant_dashboard:1:summary:2026-04-24', StatsCacheResolver::TTL_REALTIME, Mockery::type(Closure::class));
    }

    public function test_get_summary_passes_expected_key_and_ttl_for_past_date(): void
    {
        $this->service->getSummary(1, Carbon::parse('2026-04-20'));

        Cache::shouldHaveReceived('remember')
            ->once()
            ->with('tenant_dashboard:1:summary:2026-04-20', StatsCacheResolver::TTL_HISTORICAL, Mockery::type(Closure::class));
    }

    public function test_get_recent_week_sales_data_passes_expected_key_and_realtime_ttl(): void
    {
        $this->service->getRecentWeekSalesData(42);

        Cache::shouldHaveReceived('remember')
            ->with('tenant_dashboard:42:recent_week:2026-04-24', StatsCacheResolver::TTL_REALTIME, Mockery::type(Closure::class));
    }

    public function test_get_sales_data_passes_expected_key_and_range_ttl(): void
    {
        $this->service->getSalesData(7, SalesPeriod::Daily, Carbon::parse('2026-04-18'), Carbon::parse('2026-04-24'));

        Cache::shouldHaveReceived('remember')
            ->with('tenant_dashboard:7:sales:daily:2026-04-18:2026-04-24', StatsCacheResolver::TTL_REALTIME, Mockery::type(Closure::class));
    }

    public function test_get_sales_data_uses_historical_ttl_when_range_excludes_today(): void
    {
        $this->service->getSalesData(7, SalesPeriod::Weekly, Carbon::parse('2026-03-01'), Carbon::parse('2026-03-31'));

        Cache::shouldHaveReceived('remember')
            ->with('tenant_dashboard:7:sales:weekly:2026-03-01:2026-03-31', StatsCacheResolver::TTL_HISTORICAL, Mockery::type(Closure::class));
    }

    public function test_get_top_items_passes_expected_key_and_realtime_ttl(): void
    {
        $this->service->getTopItems(3, 'week', 10);

        Cache::shouldHaveReceived('remember')
            ->with('tenant_dashboard:3:top_items:week:10', StatsCacheResolver::TTL_REALTIME, Mockery::type(Closure::class));
    }

    public function test_get_hourly_distribution_passes_expected_key_and_date_ttl(): void
    {
        $this->service->getHourlyDistribution(9, Carbon::parse('2026-04-24'));

        Cache::shouldHaveReceived('remember')
            ->once()
            ->with('tenant_dashboard:9:hourly:2026-04-24', StatsCacheResolver::TTL_REALTIME, Mockery::type(Closure::class));
    }

    public function test_get_payment_method_stats_passes_expected_key_and_range_ttl(): void
    {
        $this->service->getPaymentMethodStats(4, Carbon::parse('2026-04-01'), Carbon::parse('2026-04-24'));

        Cache::shouldHaveReceived('remember')
            ->with('tenant_dashboard:4:payment_methods:2026-04-01:2026-04-24', StatsCacheResolver::TTL_REALTIME, Mockery::type(Closure::class));
    }

    public function test_get_customer_insights_passes_expected_key_and_range_ttl(): void
    {
        $this->service->getCustomerInsights(5, Carbon::parse('2026-04-01'), Carbon::parse('2026-04-24'));

        Cache::shouldHaveReceived('remember')
            ->with('tenant_dashboard:5:customer_insights:2026-04-01:2026-04-24', StatsCacheResolver::TTL_REALTIME, Mockery::type(Closure::class));
    }
}

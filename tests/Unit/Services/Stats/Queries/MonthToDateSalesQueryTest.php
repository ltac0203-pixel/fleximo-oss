<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Stats\Queries;

use App\Enums\MetricType;
use App\Models\AnalyticsCache;
use App\Models\Order;
use App\Models\Tenant;
use App\Services\Stats\Queries\MonthToDateSalesQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthToDateSalesQueryTest extends TestCase
{
    use RefreshDatabase;

    private MonthToDateSalesQuery $query;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-02-10 12:00:00'));
        $this->query = app(MonthToDateSalesQuery::class);
        $this->tenant = Tenant::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_for_range_sums_cached_past_days_and_today_realtime(): void
    {
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-02-01'),
            ['total_sales' => 1000, 'order_count' => 2, 'average_order_value' => 500]
        );
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-02-09'),
            ['total_sales' => 2000, 'order_count' => 3, 'average_order_value' => 666]
        );

        Order::factory()->forTenant($this->tenant)->forBusinessDate('2026-02-10')->paid()->totalAmount(500)->create();

        $result = $this->query->forRange($this->tenant->id, Carbon::parse('2026-02-01'), Carbon::today());

        $this->assertSame(3500, $result['total_sales']);
        $this->assertSame(6, $result['order_count']);
        $this->assertSame(583, $result['average_order_value']);
    }

    public function test_for_range_fallbacks_to_db_for_missing_cache_days(): void
    {
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-02-01'),
            ['total_sales' => 300, 'order_count' => 1, 'average_order_value' => 300]
        );

        // 2026-02-02 キャッシュ無し、DB 注文 1 件
        Order::factory()->forTenant($this->tenant)->forBusinessDate('2026-02-02')->completed()->totalAmount(700)->create();

        $result = $this->query->forRange($this->tenant->id, Carbon::parse('2026-02-01'), Carbon::today());

        $this->assertSame(1000, $result['total_sales']);
        $this->assertSame(2, $result['order_count']);
    }

    public function test_for_range_uses_only_today_when_month_start_is_today(): void
    {
        $today = Carbon::today();

        Order::factory()->forTenant($this->tenant)->forBusinessDate($today->toDateString())->paid()->totalAmount(800)->create();

        // monthStart == today、cacheEnd = yesterday < monthStart なのでキャッシュ経路は走らない
        $result = $this->query->forRange($this->tenant->id, $today, $today);

        $this->assertSame(800, $result['total_sales']);
        $this->assertSame(1, $result['order_count']);
    }
}

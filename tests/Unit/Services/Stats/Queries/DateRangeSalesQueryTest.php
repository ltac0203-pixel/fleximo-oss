<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Stats\Queries;

use App\Enums\MetricType;
use App\Models\AnalyticsCache;
use App\Models\Order;
use App\Models\Tenant;
use App\Services\Stats\Queries\DateRangeSalesQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateRangeSalesQueryTest extends TestCase
{
    use RefreshDatabase;

    private DateRangeSalesQuery $query;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-03-20 12:00:00'));
        $this->query = app(DateRangeSalesQuery::class);
        $this->tenant = Tenant::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_for_range_sums_past_cache_only_when_range_excludes_today(): void
    {
        AnalyticsCache::saveCache($this->tenant->id, MetricType::DailySales, Carbon::parse('2026-03-10'), ['total_sales' => 400, 'order_count' => 1]);
        AnalyticsCache::saveCache($this->tenant->id, MetricType::DailySales, Carbon::parse('2026-03-11'), ['total_sales' => 600, 'order_count' => 2]);

        $result = $this->query->forRange($this->tenant->id, Carbon::parse('2026-03-10'), Carbon::parse('2026-03-11'), Carbon::today());

        $this->assertSame(1000, $result['total_sales']);
        $this->assertSame(3, $result['order_count']);
    }

    public function test_for_range_adds_today_realtime_when_range_includes_today(): void
    {
        AnalyticsCache::saveCache($this->tenant->id, MetricType::DailySales, Carbon::parse('2026-03-19'), ['total_sales' => 500, 'order_count' => 1]);

        Order::factory()->forTenant($this->tenant)->forBusinessDate('2026-03-20')->paid()->totalAmount(300)->create();

        $result = $this->query->forRange($this->tenant->id, Carbon::parse('2026-03-19'), Carbon::parse('2026-03-20'), Carbon::today());

        $this->assertSame(800, $result['total_sales']);
        $this->assertSame(2, $result['order_count']);
    }

    public function test_for_range_fallbacks_to_db_for_missing_cache_days(): void
    {
        // キャッシュ無し、DB 注文のみ
        Order::factory()->forTenant($this->tenant)->forBusinessDate('2026-03-10')->paid()->totalAmount(200)->create();
        Order::factory()->forTenant($this->tenant)->forBusinessDate('2026-03-11')->completed()->totalAmount(400)->create();

        $result = $this->query->forRange($this->tenant->id, Carbon::parse('2026-03-10'), Carbon::parse('2026-03-11'), Carbon::today());

        $this->assertSame(600, $result['total_sales']);
        $this->assertSame(2, $result['order_count']);
    }
}

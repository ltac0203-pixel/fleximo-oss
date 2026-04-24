<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Stats\Queries;

use App\Enums\MetricType;
use App\Models\AnalyticsCache;
use App\Models\Order;
use App\Models\Tenant;
use App\Services\Stats\Queries\DailySalesQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailySalesQueryTest extends TestCase
{
    use RefreshDatabase;

    private DailySalesQuery $query;

    private Tenant $tenant;

    private Carbon $today;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-02-15 10:00:00'));
        $this->today = Carbon::today();
        $this->query = app(DailySalesQuery::class);
        $this->tenant = Tenant::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_for_date_aggregates_realtime_when_date_is_today(): void
    {
        Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate($this->today->toDateString())
            ->paid()
            ->totalAmount(400)
            ->create();
        Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate($this->today->toDateString())
            ->completed()
            ->totalAmount(600)
            ->create();

        // 当日は AnalyticsCache があっても無視され、DB からリアルタイムに集計される
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            $this->today,
            ['total_sales' => 9999, 'order_count' => 99, 'average_order_value' => 101]
        );

        $result = $this->query->forDate($this->tenant->id, $this->today, $this->today);

        $this->assertSame(1000, $result['total_sales']);
        $this->assertSame(2, $result['order_count']);
        $this->assertSame(500, $result['average_order_value']);
    }

    public function test_for_date_reads_cache_when_date_is_past(): void
    {
        $pastDate = Carbon::parse('2026-02-14');

        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            $pastDate,
            ['total_sales' => 1500, 'order_count' => 3, 'average_order_value' => 500]
        );

        // 過去日向けのキャッシュが優先され、DB には存在しない注文でも無視される
        Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate($pastDate->toDateString())
            ->paid()
            ->totalAmount(99999)
            ->create();

        $result = $this->query->forDate($this->tenant->id, $pastDate, $this->today);

        $this->assertSame(1500, $result['total_sales']);
        $this->assertSame(3, $result['order_count']);
        $this->assertSame(500, $result['average_order_value']);
    }

    public function test_for_date_returns_zeros_when_past_cache_missing(): void
    {
        $pastDate = Carbon::parse('2026-02-13');

        // キャッシュも注文も存在しない過去日
        $result = $this->query->forDate($this->tenant->id, $pastDate, $this->today);

        $this->assertSame(['total_sales' => 0, 'order_count' => 0, 'average_order_value' => 0], $result);
    }
}

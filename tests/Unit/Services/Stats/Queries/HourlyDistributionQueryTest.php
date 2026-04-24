<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Stats\Queries;

use App\Enums\MetricType;
use App\Models\AnalyticsCache;
use App\Models\Order;
use App\Models\Tenant;
use App\Services\Stats\Queries\HourlyDistributionQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HourlyDistributionQueryTest extends TestCase
{
    use RefreshDatabase;

    private HourlyDistributionQuery $query;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->query = app(HourlyDistributionQuery::class);
        $this->tenant = Tenant::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_for_date_aggregates_realtime_for_today_and_zero_fills_24_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-15 12:30:00'));
        $today = Carbon::today();

        // 12 時に 1 件
        Order::factory()->forTenant($this->tenant)->forBusinessDate($today->toDateString())->paid()->totalAmount(500)->create();

        Carbon::setTestNow(Carbon::parse('2026-02-15 14:00:00'));
        // 14 時に 2 件
        Order::factory()->forTenant($this->tenant)->forBusinessDate($today->toDateString())->completed()->totalAmount(300)->create();
        Order::factory()->forTenant($this->tenant)->forBusinessDate($today->toDateString())->completed()->totalAmount(700)->create();

        Carbon::setTestNow(Carbon::parse('2026-02-15 15:00:00'));

        $result = $this->query->forDate($this->tenant->id, $today, Carbon::today());

        $this->assertCount(24, $result);
        $this->assertSame(['hour' => 12, 'orders' => 1, 'sales' => 500], $result[12]);
        $this->assertSame(['hour' => 14, 'orders' => 2, 'sales' => 1000], $result[14]);
        $this->assertSame(['hour' => 0, 'orders' => 0, 'sales' => 0], $result[0]);
        $this->assertSame(['hour' => 23, 'orders' => 0, 'sales' => 0], $result[23]);
    }

    public function test_for_date_reads_cached_hourly_stats_for_past_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-20 10:00:00'));

        $pastDate = Carbon::parse('2026-02-19');
        $cachedHourlyStats = [];
        for ($h = 0; $h < 24; $h++) {
            $cachedHourlyStats[] = ['hour' => $h, 'orders' => $h === 11 ? 5 : 0, 'sales' => $h === 11 ? 2500 : 0];
        }

        AnalyticsCache::saveCache($this->tenant->id, MetricType::HourlyDistribution, $pastDate, ['hourly_stats' => $cachedHourlyStats]);

        $result = $this->query->forDate($this->tenant->id, $pastDate, Carbon::today());

        $this->assertSame($cachedHourlyStats, $result);
    }

    public function test_for_date_fallbacks_to_db_realtime_when_past_cache_missing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-25 09:00:00'));

        // 2026-02-24 をシミュレート: setTestNow を 2026-02-24 08:00:00 にして注文を作成
        Carbon::setTestNow(Carbon::parse('2026-02-24 08:00:00'));
        Order::factory()->forTenant($this->tenant)->forBusinessDate('2026-02-24')->paid()->totalAmount(400)->create();

        Carbon::setTestNow(Carbon::parse('2026-02-25 09:00:00'));

        // キャッシュ未生成 → DB から時間帯別集計にフォールバック
        $result = $this->query->forDate($this->tenant->id, Carbon::parse('2026-02-24'), Carbon::today());

        $this->assertCount(24, $result);
        $this->assertSame(['hour' => 8, 'orders' => 1, 'sales' => 400], $result[8]);
    }
}

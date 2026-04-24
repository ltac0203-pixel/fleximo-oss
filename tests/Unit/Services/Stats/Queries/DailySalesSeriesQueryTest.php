<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Stats\Queries;

use App\Enums\MetricType;
use App\Models\AnalyticsCache;
use App\Models\Order;
use App\Models\Tenant;
use App\Services\Stats\Queries\DailySalesSeriesQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailySalesSeriesQueryTest extends TestCase
{
    use RefreshDatabase;

    private DailySalesSeriesQuery $query;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-03-10 09:00:00'));
        $this->query = app(DailySalesSeriesQuery::class);
        $this->tenant = Tenant::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_for_range_returns_zero_filled_dictionary_for_all_days(): void
    {
        $result = $this->query->forRange($this->tenant->id, Carbon::parse('2026-03-05'), Carbon::parse('2026-03-07'), Carbon::today());

        $this->assertSame(['2026-03-05', '2026-03-06', '2026-03-07'], array_keys($result));
        foreach ($result as $entry) {
            $this->assertSame(['total_sales' => 0, 'order_count' => 0, 'average_order_value' => 0], $entry);
        }
    }

    public function test_for_range_overrides_today_with_realtime(): void
    {
        AnalyticsCache::saveCache($this->tenant->id, MetricType::DailySales, Carbon::today(), ['total_sales' => 9999, 'order_count' => 99, 'average_order_value' => 101]);

        Order::factory()->forTenant($this->tenant)->forBusinessDate('2026-03-10')->paid()->totalAmount(400)->create();

        $result = $this->query->forRange($this->tenant->id, Carbon::parse('2026-03-09'), Carbon::parse('2026-03-10'), Carbon::today());

        $this->assertSame(400, $result['2026-03-10']['total_sales']);
        $this->assertSame(1, $result['2026-03-10']['order_count']);
    }

    public function test_for_range_fallbacks_to_db_for_missing_past_cache(): void
    {
        Order::factory()->forTenant($this->tenant)->forBusinessDate('2026-03-05')->paid()->totalAmount(300)->create();
        Order::factory()->forTenant($this->tenant)->forBusinessDate('2026-03-05')->completed()->totalAmount(700)->create();

        $result = $this->query->forRange($this->tenant->id, Carbon::parse('2026-03-05'), Carbon::parse('2026-03-06'), Carbon::today());

        $this->assertSame(1000, $result['2026-03-05']['total_sales']);
        $this->assertSame(2, $result['2026-03-05']['order_count']);
        $this->assertSame(500, $result['2026-03-05']['average_order_value']);
        $this->assertSame(0, $result['2026-03-06']['total_sales']);
    }
}

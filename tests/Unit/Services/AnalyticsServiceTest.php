<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\MetricType;
use App\Exceptions\TenantDailyAnalyticsPartialFailureException;
use App\Jobs\AggregateDailyAnalyticsJob;
use App\Models\AnalyticsCache;
use App\Models\HourlyOrderStat;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $service;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnalyticsService;
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->customer()->create();
    }

    public function test_aggregate_daily_sales_returns_correct_totals(): void
    {
        $date = Carbon::today();

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(1000)
            ->create();

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->accepted()
            ->totalAmount(2000)
            ->create();

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->completed()
            ->totalAmount(3000)
            ->create();

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->pendingPayment()
            ->totalAmount(5000)
            ->create();

        $result = $this->service->aggregateDailySales($this->tenant->id, $date);

        $this->assertEquals(6000, $result['total_sales']);
        $this->assertEquals(3, $result['order_count']);
        $this->assertEquals(2000, $result['average_order_value']);
    }

    public function test_aggregate_daily_sales_returns_zero_when_no_orders(): void
    {
        $date = Carbon::today();

        $result = $this->service->aggregateDailySales($this->tenant->id, $date);

        $this->assertEquals(0, $result['total_sales']);
        $this->assertEquals(0, $result['order_count']);
        $this->assertEquals(0, $result['average_order_value']);
    }

    public function test_aggregate_monthly_sales_includes_daily_breakdown(): void
    {
        $year = 2026;
        $month = 1;

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate('2026-01-15')
            ->paid()
            ->totalAmount(1500)
            ->create();

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate('2026-01-20')
            ->completed()
            ->totalAmount(2500)
            ->create();

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate('2026-01-20')
            ->accepted()
            ->totalAmount(1000)
            ->create();

        $result = $this->service->aggregateMonthlySales($this->tenant->id, $year, $month);

        $this->assertEquals(5000, $result['total_sales']);
        $this->assertEquals(3, $result['order_count']);
        $this->assertArrayHasKey('daily_breakdown', $result);
        $this->assertArrayHasKey('2026-01-15', $result['daily_breakdown']);
        $this->assertArrayHasKey('2026-01-20', $result['daily_breakdown']);
        $this->assertEquals(1500, $result['daily_breakdown']['2026-01-15']['sales']);
        $this->assertEquals(1, $result['daily_breakdown']['2026-01-15']['count']);
        $this->assertEquals(3500, $result['daily_breakdown']['2026-01-20']['sales']);
        $this->assertEquals(2, $result['daily_breakdown']['2026-01-20']['count']);
    }

    public function test_aggregate_daily_order_stats_returns_status_breakdown(): void
    {
        $date = Carbon::today();

        Order::factory(2)
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->create();

        Order::factory(3)
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->accepted()
            ->create();

        Order::factory(1)
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->cancelled()
            ->create();

        $result = $this->service->aggregateDailyOrderStats($this->tenant->id, $date);

        $this->assertEquals(2, $result['paid']);
        $this->assertEquals(3, $result['accepted']);
        $this->assertEquals(1, $result['cancelled']);
        $this->assertEquals(0, $result['completed']);
    }

    public function test_aggregate_top_menu_items_returns_ranked_items(): void
    {
        $year = 2026;
        $month = 1;

        $menuItem1 = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'ラーメン',
        ]);
        $menuItem2 = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'チャーハン',
        ]);

        $order1 = Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate('2026-01-15')
            ->completed()
            ->create();

        $order2 = Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate('2026-01-16')
            ->paid()
            ->create();

        OrderItem::factory()->forOrder($order1)->fromMenuItem($menuItem1)->create(['quantity' => 5, 'price' => 1000]);
        OrderItem::factory()->forOrder($order1)->fromMenuItem($menuItem2)->create(['quantity' => 2, 'price' => 800]);
        OrderItem::factory()->forOrder($order2)->fromMenuItem($menuItem1)->create(['quantity' => 3, 'price' => 1000]);

        $result = $this->service->aggregateTopMenuItems($this->tenant->id, $year, $month, 10);

        $this->assertCount(2, $result);
        $this->assertEquals('ラーメン', $result[0]['name']);
        $this->assertEquals(8, $result[0]['quantity']);
        $this->assertEquals(8000, $result[0]['total_amount']);
        $this->assertEquals('チャーハン', $result[1]['name']);
        $this->assertEquals(2, $result[1]['quantity']);
    }

    public function test_aggregate_hourly_distribution_returns_24_hours(): void
    {
        $date = Carbon::today();

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(1000)
            ->create(['paid_at' => $date->copy()->setHour(10)]);

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(2000)
            ->create(['paid_at' => $date->copy()->setHour(14)]);

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(1500)
            ->create(['paid_at' => $date->copy()->setHour(14)]);

        $result = $this->service->aggregateHourlyDistribution($this->tenant->id, $date);

        $this->assertCount(24, $result);
        $this->assertEquals(1, $result[10]['order_count']);
        $this->assertEquals(1000, $result[10]['total_amount']);
        $this->assertEquals(2, $result[14]['order_count']);
        $this->assertEquals(3500, $result[14]['total_amount']);
        $this->assertEquals(0, $result[0]['order_count']);
    }

    public function test_aggregate_platform_daily_sales_includes_all_tenants(): void
    {
        $date = Carbon::today();
        $tenant2 = Tenant::factory()->create();

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(1000)
            ->create();

        Order::factory()
            ->forTenant($tenant2)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(2000)
            ->create();

        $result = $this->service->aggregatePlatformDailySales($date);

        $this->assertEquals(3000, $result['total_sales']);
        $this->assertEquals(2, $result['order_count']);
        $this->assertCount(2, $result['tenant_breakdown']);
    }

    public function test_save_cache_and_get_cached_analytics(): void
    {
        $date = Carbon::today();
        $data = ['total_sales' => 10000, 'order_count' => 5];

        $this->service->saveCache($this->tenant->id, MetricType::DailySales, $date, $data);

        $cached = $this->service->getCachedAnalytics($this->tenant->id, MetricType::DailySales, $date);

        $this->assertNotNull($cached);
        $this->assertEquals(10000, $cached['total_sales']);
        $this->assertEquals(5, $cached['order_count']);
    }

    public function test_save_cache_updates_existing_cache(): void
    {
        $date = Carbon::today();

        $this->service->saveCache($this->tenant->id, MetricType::DailySales, $date, ['total_sales' => 5000]);
        $this->service->saveCache($this->tenant->id, MetricType::DailySales, $date, ['total_sales' => 10000]);

        $this->assertDatabaseCount('analytics_cache', 1);
        $cached = $this->service->getCachedAnalytics($this->tenant->id, MetricType::DailySales, $date);
        $this->assertEquals(10000, $cached['total_sales']);
    }

    public function test_update_hourly_stats_creates_records(): void
    {
        $date = Carbon::today();

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(1000)
            ->create(['paid_at' => $date->copy()->setHour(12)]);

        $this->service->updateHourlyStats($this->tenant->id, $date);

        $stats = HourlyOrderStat::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('date', $date->toDateString())
            ->get();

        $this->assertCount(24, $stats);

        $stat12 = $stats->firstWhere('hour', 12);
        $this->assertEquals(1, $stat12->order_count);
        $this->assertEquals(1000, $stat12->total_amount);
    }

    public function test_aggregate_tenant_daily_analytics_saves_all_metrics(): void
    {
        $date = Carbon::today();

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(1000)
            ->create(['paid_at' => $date->copy()->setHour(10)]);

        $this->service->aggregateTenantDailyAnalytics($this->tenant->id, $date);

        $this->assertNotNull(AnalyticsCache::getCached($this->tenant->id, MetricType::DailySales, $date));
        $this->assertNotNull(AnalyticsCache::getCached($this->tenant->id, MetricType::DailyOrderStats, $date));
        $this->assertNotNull(AnalyticsCache::getCached($this->tenant->id, MetricType::HourlyDistribution, $date));
    }

    public function test_aggregate_all_tenants_daily_analytics(): void
    {
        $date = Carbon::today();
        $tenant2 = Tenant::factory()->create();

        Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(1000)
            ->create(['paid_at' => $date->copy()->setHour(10)]);

        Order::factory()
            ->forTenant($tenant2)
            ->forUser($this->user)
            ->forBusinessDate($date->toDateString())
            ->paid()
            ->totalAmount(2000)
            ->create(['paid_at' => $date->copy()->setHour(11)]);

        $this->service->aggregateAllTenantsDailyAnalytics($date);

        $this->assertNotNull(AnalyticsCache::getCached($this->tenant->id, MetricType::DailySales, $date));
        $this->assertNotNull(AnalyticsCache::getCached($tenant2->id, MetricType::DailySales, $date));

        $platformSales = AnalyticsCache::getCached(null, MetricType::DailySales, $date);
        $this->assertNotNull($platformSales);
        $this->assertEquals(3000, $platformSales['total_sales']);
    }

    public function test_aggregate_all_tenants_daily_analytics_continues_within_same_chunk_after_failure(): void
    {
        $date = Carbon::today();
        Tenant::factory()->count(2)->create();

        $tenantIds = Tenant::query()->orderBy('id')->pluck('id')->all();
        $failingTenantId = $tenantIds[1];

        $service = new class($failingTenantId) extends AnalyticsService
        {
            /** @var array<int, int> */
            public array $processedTenantIds = [];

            public function __construct(
                private readonly int $failingTenantId
            ) {}

            protected function aggregateTenantDailyAnalyticsBatch(int $tenantId, Carbon $date): array
            {
                $this->processedTenantIds[] = $tenantId;

                if ($tenantId === $this->failingTenantId) {
                    throw new \RuntimeException('Simulated tenant failure');
                }

                return [[
                    'tenant_id' => $tenantId,
                    'metric_type' => MetricType::DailySales,
                    'date' => $date,
                    'data' => [
                        'total_sales' => 1000,
                        'order_count' => 1,
                        'average_order_value' => 1000,
                    ],
                ]];
            }
        };

        try {
            $service->aggregateAllTenantsDailyAnalytics($date);
            $this->fail('Expected TenantDailyAnalyticsPartialFailureException was not thrown.');
        } catch (TenantDailyAnalyticsPartialFailureException $exception) {
            $this->assertSame(1, $exception->failureCount);
            $this->assertSame([$failingTenantId], $exception->failedTenantIds);
        }

        $this->assertSame($tenantIds, $service->processedTenantIds);

        foreach ($tenantIds as $tenantId) {
            $cached = AnalyticsCache::getCached($tenantId, MetricType::DailySales, $date);

            if ($tenantId === $failingTenantId) {
                $this->assertNull($cached);
            } else {
                $this->assertNotNull($cached);
            }
        }

        $platformSales = AnalyticsCache::getCached(null, MetricType::DailySales, $date);
        $this->assertNotNull($platformSales);
    }

    public function test_aggregate_all_tenants_daily_analytics_continues_to_next_chunk_after_failure(): void
    {
        $date = Carbon::today();
        Tenant::factory()->count(100)->create();

        $tenantIds = Tenant::query()->orderBy('id')->pluck('id')->all();
        $failingTenantId = $tenantIds[0];
        $lastTenantId = $tenantIds[count($tenantIds) - 1];

        $service = new class($failingTenantId) extends AnalyticsService
        {
            /** @var array<int, int> */
            public array $processedTenantIds = [];

            public function __construct(
                private readonly int $failingTenantId
            ) {}

            protected function aggregateTenantDailyAnalyticsBatch(int $tenantId, Carbon $date): array
            {
                $this->processedTenantIds[] = $tenantId;

                if ($tenantId === $this->failingTenantId) {
                    throw new \RuntimeException('Simulated tenant failure');
                }

                return [[
                    'tenant_id' => $tenantId,
                    'metric_type' => MetricType::DailySales,
                    'date' => $date,
                    'data' => [
                        'total_sales' => 1000,
                        'order_count' => 1,
                        'average_order_value' => 1000,
                    ],
                ]];
            }
        };

        try {
            $service->aggregateAllTenantsDailyAnalytics($date);
            $this->fail('Expected TenantDailyAnalyticsPartialFailureException was not thrown.');
        } catch (TenantDailyAnalyticsPartialFailureException $exception) {
            $this->assertSame(1, $exception->failureCount);
            $this->assertSame([$failingTenantId], $exception->failedTenantIds);
        }

        $this->assertCount(count($tenantIds), $service->processedTenantIds);
        $this->assertContains($lastTenantId, $service->processedTenantIds);
        $this->assertNull(AnalyticsCache::getCached($failingTenantId, MetricType::DailySales, $date));
        $this->assertNotNull(AnalyticsCache::getCached($lastTenantId, MetricType::DailySales, $date));

        $platformSales = AnalyticsCache::getCached(null, MetricType::DailySales, $date);
        $this->assertNotNull($platformSales);
    }

    public function test_dispatch_all_tenants_daily_analytics_dispatches_tenant_jobs(): void
    {
        Queue::fake();

        $date = Carbon::today();
        $tenant2 = Tenant::factory()->create();

        $dispatchedCount = $this->service->dispatchAllTenantsDailyAnalytics($date);

        $this->assertSame(2, $dispatchedCount);

        Queue::assertPushed(AggregateDailyAnalyticsJob::class, 2);
        Queue::assertPushed(AggregateDailyAnalyticsJob::class, function ($job) use ($date) {
            return $job->tenantId === $this->tenant->id
                && $job->date->toDateString() === $date->toDateString();
        });
        Queue::assertPushed(AggregateDailyAnalyticsJob::class, function ($job) use ($date, $tenant2) {
            return $job->tenantId === $tenant2->id
                && $job->date->toDateString() === $date->toDateString();
        });
    }

    public function test_aggregate_tenant_monthly_analytics_saves_monthly_metrics(): void
    {
        $year = 2026;
        $month = 1;

        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $order = Order::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->forBusinessDate('2026-01-15')
            ->completed()
            ->totalAmount(3000)
            ->create();

        OrderItem::factory()->forOrder($order)->fromMenuItem($menuItem)->create(['quantity' => 2, 'price' => 1500]);

        $this->service->aggregateTenantMonthlyAnalytics($this->tenant->id, $year, $month);

        $startDate = Carbon::create($year, $month, 1);

        $monthlySales = AnalyticsCache::getCached($this->tenant->id, MetricType::MonthlySales, $startDate);
        $this->assertNotNull($monthlySales);
        $this->assertEquals(3000, $monthlySales['total_sales']);

        $topMenuItems = AnalyticsCache::getCached($this->tenant->id, MetricType::TopMenuItems, $startDate);
        $this->assertNotNull($topMenuItems);
        $this->assertCount(1, $topMenuItems);
    }

    public function test_upsert_batch_creates_24_hourly_records(): void
    {
        $date = Carbon::today();
        $hourlyData = [];

        for ($h = 0; $h < 24; $h++) {
            $hourlyData[] = [
                'hour' => $h,
                'order_count' => $h * 2,
                'total_amount' => $h * 1000,
            ];
        }

        HourlyOrderStat::upsertBatch($this->tenant->id, $date, $hourlyData);

        $stats = HourlyOrderStat::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('date', $date->toDateString())
            ->get();

        $this->assertCount(24, $stats);

        $stat10 = $stats->firstWhere('hour', 10);
        $this->assertEquals(20, $stat10->order_count);
        $this->assertEquals(10000, $stat10->total_amount);
    }

    public function test_upsert_batch_updates_existing_records(): void
    {
        $date = Carbon::today();

        // 初回データ作成
        $initialData = [
            ['hour' => 10, 'order_count' => 5, 'total_amount' => 5000],
            ['hour' => 11, 'order_count' => 3, 'total_amount' => 3000],
        ];
        HourlyOrderStat::upsertBatch($this->tenant->id, $date, $initialData);

        // 更新データ
        $updatedData = [
            ['hour' => 10, 'order_count' => 10, 'total_amount' => 10000],
            ['hour' => 11, 'order_count' => 6, 'total_amount' => 6000],
        ];
        HourlyOrderStat::upsertBatch($this->tenant->id, $date, $updatedData);

        $stats = HourlyOrderStat::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('date', $date->toDateString())
            ->get();

        // レコード数は2のまま（上書きされる）
        $this->assertCount(2, $stats);

        $stat10 = $stats->firstWhere('hour', 10);
        $this->assertEquals(10, $stat10->order_count);
        $this->assertEquals(10000, $stat10->total_amount);
    }

    public function test_analytics_cache_batch_saves_multiple_entries(): void
    {
        $date = Carbon::today();
        $tenant2 = Tenant::factory()->create();

        $entries = [
            [
                'tenant_id' => $this->tenant->id,
                'metric_type' => MetricType::DailySales,
                'date' => $date,
                'data' => ['total_sales' => 1000, 'order_count' => 5],
            ],
            [
                'tenant_id' => $this->tenant->id,
                'metric_type' => MetricType::DailyOrderStats,
                'date' => $date,
                'data' => ['paid' => 3, 'completed' => 2],
            ],
            [
                'tenant_id' => $tenant2->id,
                'metric_type' => MetricType::DailySales,
                'date' => $date,
                'data' => ['total_sales' => 2000, 'order_count' => 10],
            ],
            [
                'tenant_id' => null,
                'metric_type' => MetricType::DailySales,
                'date' => $date,
                'data' => ['total_sales' => 3000, 'order_count' => 15],
            ],
        ];

        AnalyticsCache::saveCacheBatch($entries);

        $this->assertDatabaseCount('analytics_cache', 4);

        $cached1 = AnalyticsCache::getCached($this->tenant->id, MetricType::DailySales, $date);
        $this->assertEquals(1000, $cached1['total_sales']);

        $cached2 = AnalyticsCache::getCached($this->tenant->id, MetricType::DailyOrderStats, $date);
        $this->assertEquals(3, $cached2['paid']);

        $cached3 = AnalyticsCache::getCached($tenant2->id, MetricType::DailySales, $date);
        $this->assertEquals(2000, $cached3['total_sales']);

        $cachedPlatform = AnalyticsCache::getCached(null, MetricType::DailySales, $date);
        $this->assertEquals(3000, $cachedPlatform['total_sales']);
    }

    public function test_analytics_cache_batch_updates_existing_entries(): void
    {
        $date = Carbon::today();

        // 初回保存
        $entries1 = [
            [
                'tenant_id' => $this->tenant->id,
                'metric_type' => MetricType::DailySales,
                'date' => $date,
                'data' => ['total_sales' => 1000],
            ],
        ];
        AnalyticsCache::saveCacheBatch($entries1);

        // 更新
        $entries2 = [
            [
                'tenant_id' => $this->tenant->id,
                'metric_type' => MetricType::DailySales,
                'date' => $date,
                'data' => ['total_sales' => 5000],
            ],
        ];
        AnalyticsCache::saveCacheBatch($entries2);

        $this->assertDatabaseCount('analytics_cache', 1);

        $cached = AnalyticsCache::getCached($this->tenant->id, MetricType::DailySales, $date);
        $this->assertEquals(5000, $cached['total_sales']);
    }

    public function test_upsert_batch_handles_empty_array(): void
    {
        $date = Carbon::today();

        HourlyOrderStat::upsertBatch($this->tenant->id, $date, []);

        $stats = HourlyOrderStat::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->get();

        $this->assertCount(0, $stats);
    }

    public function test_save_cache_batch_handles_empty_array(): void
    {
        AnalyticsCache::saveCacheBatch([]);

        $this->assertDatabaseCount('analytics_cache', 0);
    }
}

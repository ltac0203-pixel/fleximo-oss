<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\MetricType;
use App\Models\AnalyticsCache;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\TenantStatsRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantStatsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TenantStatsRepository $repository;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-01-10 12:00:00'));

        $this->repository = app(TenantStatsRepository::class);
        $this->tenant = Tenant::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_get_month_to_date_stats_aggregates_cached_days_and_today_realtime(): void
    {
        $monthStart = Carbon::parse('2026-01-01');
        $today = Carbon::today();

        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-01-01'),
            ['total_sales' => 1000, 'order_count' => 1, 'average_order_value' => 1000]
        );
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-01-02'),
            ['total_sales' => 2000, 'order_count' => 2, 'average_order_value' => 1000]
        );

        Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate($today->toDateString())
            ->paid()
            ->totalAmount(300)
            ->create();
        Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate($today->toDateString())
            ->completed()
            ->totalAmount(700)
            ->create();

        $result = $this->repository->getMonthToDateStats($this->tenant->id, $monthStart, $today);

        $this->assertSame(4000, $result['total_sales']);
        $this->assertSame(5, $result['order_count']);
        $this->assertSame(800, $result['average_order_value']);
    }

    public function test_get_date_range_stats_uses_cached_values_for_past_range(): void
    {
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-01-03'),
            ['total_sales' => 500, 'order_count' => 1, 'average_order_value' => 500]
        );
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-01-04'),
            ['total_sales' => 700, 'order_count' => 2, 'average_order_value' => 350]
        );

        $result = $this->repository->getDateRangeStats(
            $this->tenant->id,
            Carbon::parse('2026-01-03'),
            Carbon::parse('2026-01-04')
        );

        $this->assertSame(1200, $result['total_sales']);
        $this->assertSame(3, $result['order_count']);
    }

    public function test_get_date_range_stats_adds_today_realtime_when_included(): void
    {
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-01-08'),
            ['total_sales' => 400, 'order_count' => 1, 'average_order_value' => 400]
        );
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-01-09'),
            ['total_sales' => 600, 'order_count' => 3, 'average_order_value' => 200]
        );

        $today = Carbon::today()->toDateString();
        Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate($today)
            ->accepted()
            ->totalAmount(300)
            ->create();
        Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate($today)
            ->completed()
            ->totalAmount(600)
            ->create();

        $result = $this->repository->getDateRangeStats(
            $this->tenant->id,
            Carbon::parse('2026-01-08'),
            Carbon::parse('2026-01-10')
        );

        $this->assertSame(1900, $result['total_sales']);
        $this->assertSame(6, $result['order_count']);
    }

    public function test_get_daily_stats_for_range_fills_missing_days_and_overrides_today_with_realtime(): void
    {
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-01-08'),
            ['total_sales' => 300, 'order_count' => 1, 'average_order_value' => 300]
        );
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-01-09'),
            ['total_sales' => 500, 'order_count' => 2, 'average_order_value' => 250]
        );
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::today(),
            ['total_sales' => 9999, 'order_count' => 99, 'average_order_value' => 101]
        );

        $today = Carbon::today()->toDateString();
        Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate($today)
            ->paid()
            ->totalAmount(200)
            ->create();
        Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate($today)
            ->completed()
            ->totalAmount(400)
            ->create();

        $result = $this->repository->getDailyStatsForRange(
            $this->tenant->id,
            Carbon::parse('2026-01-07'),
            Carbon::parse('2026-01-10')
        );

        $this->assertSame(
            ['2026-01-07', '2026-01-08', '2026-01-09', '2026-01-10'],
            array_keys($result)
        );

        $this->assertSame(
            ['total_sales' => 0, 'order_count' => 0, 'average_order_value' => 0],
            $result['2026-01-07']
        );
        $this->assertSame(
            ['total_sales' => 300, 'order_count' => 1, 'average_order_value' => 300],
            $result['2026-01-08']
        );
        $this->assertSame(
            ['total_sales' => 500, 'order_count' => 2, 'average_order_value' => 250],
            $result['2026-01-09']
        );
        $this->assertSame(600, $result['2026-01-10']['total_sales']);
        $this->assertSame(2, $result['2026-01-10']['order_count']);
        $this->assertSame(300, $result['2026-01-10']['average_order_value']);
    }

    public function test_get_month_to_date_stats_fallbacks_to_db_for_missing_cache_days(): void
    {
        $monthStart = Carbon::parse('2026-01-01');
        $today = Carbon::today();

        // 2026-01-01 はキャッシュあり
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-01-01'),
            ['total_sales' => 1000, 'order_count' => 1, 'average_order_value' => 1000]
        );

        // 2026-01-02 はキャッシュなし・DB注文あり
        Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate('2026-01-02')
            ->paid()
            ->totalAmount(500)
            ->create();

        // 当日分リアルタイム（0件）
        $result = $this->repository->getMonthToDateStats($this->tenant->id, $monthStart, $today);

        $this->assertSame(1500, $result['total_sales']);
        $this->assertSame(2, $result['order_count']);
    }

    public function test_get_date_range_stats_fallbacks_to_db_for_missing_cache_days(): void
    {
        // 2026-01-01 はキャッシュあり
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-01-01'),
            ['total_sales' => 1000, 'order_count' => 1, 'average_order_value' => 1000]
        );

        // 2026-01-02 はキャッシュなし・DB注文あり
        Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate('2026-01-02')
            ->paid()
            ->totalAmount(500)
            ->create();

        $result = $this->repository->getDateRangeStats(
            $this->tenant->id,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-02')
        );

        $this->assertSame(1500, $result['total_sales']);
        $this->assertSame(2, $result['order_count']);
    }

    public function test_get_daily_stats_for_range_fallbacks_to_db_for_missing_cache_days(): void
    {
        // 2026-01-01 はキャッシュあり
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-01-01'),
            ['total_sales' => 800, 'order_count' => 2, 'average_order_value' => 400]
        );

        // 2026-01-02 はキャッシュなし・DB注文あり
        Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate('2026-01-02')
            ->paid()
            ->totalAmount(300)
            ->create();
        Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate('2026-01-02')
            ->completed()
            ->totalAmount(700)
            ->create();

        // 2026-01-03 はキャッシュも注文も無し

        $result = $this->repository->getDailyStatsForRange(
            $this->tenant->id,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-03')
        );

        // 2026-01-01: キャッシュ値を使用
        $this->assertSame(800, $result['2026-01-01']['total_sales']);
        $this->assertSame(2, $result['2026-01-01']['order_count']);
        $this->assertSame(400, $result['2026-01-01']['average_order_value']);

        // 2026-01-02: DBフォールバック値を使用
        $this->assertSame(1000, $result['2026-01-02']['total_sales']);
        $this->assertSame(2, $result['2026-01-02']['order_count']);
        $this->assertSame(500, $result['2026-01-02']['average_order_value']);

        // 2026-01-03: キャッシュも注文も無いので 0
        $this->assertSame(0, $result['2026-01-03']['total_sales']);
        $this->assertSame(0, $result['2026-01-03']['order_count']);
        $this->assertSame(0, $result['2026-01-03']['average_order_value']);
    }

    public function test_today_reference_is_consistent_across_methods_when_date_changes_mid_process(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-10 23:59:59'));
        $repository = app(TenantStatsRepository::class);

        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-01-09'),
            ['total_sales' => 400, 'order_count' => 1, 'average_order_value' => 400]
        );
        AnalyticsCache::saveCache(
            $this->tenant->id,
            MetricType::DailySales,
            Carbon::parse('2026-01-10'),
            ['total_sales' => 9999, 'order_count' => 99, 'average_order_value' => 101]
        );

        Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate('2026-01-10')
            ->completed()
            ->totalAmount(500)
            ->create();

        $rangeStatsBeforeMidnight = $repository->getDateRangeStats(
            $this->tenant->id,
            Carbon::parse('2026-01-09'),
            Carbon::parse('2026-01-10')
        );

        Carbon::setTestNow(Carbon::parse('2026-01-11 00:00:01'));

        $dailyStatsAfterMidnight = $repository->getDailyStatsForRange(
            $this->tenant->id,
            Carbon::parse('2026-01-09'),
            Carbon::parse('2026-01-10')
        );

        $this->assertSame(900, $rangeStatsBeforeMidnight['total_sales']);
        $this->assertSame(2, $rangeStatsBeforeMidnight['order_count']);
        $this->assertSame(500, $dailyStatsAfterMidnight['2026-01-10']['total_sales']);
        $this->assertSame(1, $dailyStatsAfterMidnight['2026-01-10']['order_count']);
    }

    public function test_get_top_items_filters_by_tenant_status_and_start_date_and_applies_limit(): void
    {
        $startDate = Carbon::parse('2026-01-08');

        $coffee = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'コーヒー',
            'price' => 500,
        ]);
        $latte = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'ラテ',
            'price' => 300,
        ]);
        $tea = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '紅茶',
            'price' => 200,
        ]);

        $includedCompleted = Order::factory()
            ->forTenant($this->tenant)
            ->completed()
            ->forBusinessDate('2026-01-08')
            ->create();
        OrderItem::factory()->for($includedCompleted)->create([
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $coffee->id,
            'name' => $coffee->name,
            'price' => 500,
            'quantity' => 2,
        ]);

        $includedPaid = Order::factory()
            ->forTenant($this->tenant)
            ->paid()
            ->forBusinessDate('2026-01-09')
            ->create();
        OrderItem::factory()->for($includedPaid)->create([
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $coffee->id,
            'name' => $coffee->name,
            'price' => 500,
            'quantity' => 1,
        ]);
        OrderItem::factory()->for($includedPaid)->create([
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $latte->id,
            'name' => $latte->name,
            'price' => 300,
            'quantity' => 4,
        ]);

        $excludedCancelled = Order::factory()
            ->forTenant($this->tenant)
            ->cancelled()
            ->forBusinessDate('2026-01-09')
            ->create();
        OrderItem::factory()->for($excludedCancelled)->create([
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $tea->id,
            'name' => $tea->name,
            'price' => 200,
            'quantity' => 100,
        ]);

        $excludedOld = Order::factory()
            ->forTenant($this->tenant)
            ->completed()
            ->forBusinessDate('2026-01-07')
            ->create();
        OrderItem::factory()->for($excludedOld)->create([
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $latte->id,
            'name' => $latte->name,
            'price' => 300,
            'quantity' => 100,
        ]);

        $otherTenant = Tenant::factory()->create();
        $otherMenuItem = MenuItem::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => '他テナント商品',
            'price' => 400,
        ]);
        $otherTenantOrder = Order::factory()
            ->forTenant($otherTenant)
            ->completed()
            ->forBusinessDate('2026-01-10')
            ->create();
        OrderItem::factory()->for($otherTenantOrder)->create([
            'tenant_id' => $otherTenant->id,
            'menu_item_id' => $otherMenuItem->id,
            'name' => $otherMenuItem->name,
            'price' => 400,
            'quantity' => 200,
        ]);

        $result = $this->repository->getTopItems($this->tenant->id, $startDate, 2);

        $this->assertCount(2, $result);
        $this->assertSame($latte->id, $result[0]->menu_item_id);
        $this->assertSame('ラテ', $result[0]->name);
        $this->assertSame(4, (int) $result[0]->total_quantity);
        $this->assertSame(1200, (int) $result[0]->total_revenue);
        $this->assertSame($coffee->id, $result[1]->menu_item_id);
        $this->assertSame('コーヒー', $result[1]->name);
        $this->assertSame(3, (int) $result[1]->total_quantity);
        $this->assertSame(1500, (int) $result[1]->total_revenue);
    }

    public function test_get_payment_method_stats_filters_by_tenant_date_and_completed_status_and_groups_by_method(): void
    {
        $includedOrder1 = Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate('2026-01-09')
            ->create();
        $includedOrder2 = Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate('2026-01-10')
            ->create();

        Payment::factory()->forOrder($includedOrder1)->card()->completed()->amount(1000)->create();
        Payment::factory()->forOrder($includedOrder1)->paypay()->completed()->amount(500)->create();
        Payment::factory()->forOrder($includedOrder2)->card()->completed()->amount(2000)->create();

        // 失敗決済は集計対象外
        Payment::factory()->forOrder($includedOrder1)->card()->failed()->amount(9999)->create();

        $excludedOutOfRangeOrder = Order::factory()
            ->forTenant($this->tenant)
            ->forBusinessDate('2026-01-08')
            ->create();
        Payment::factory()->forOrder($excludedOutOfRangeOrder)->paypay()->completed()->amount(7000)->create();

        $otherTenant = Tenant::factory()->create();
        $otherTenantOrder = Order::factory()
            ->forTenant($otherTenant)
            ->forBusinessDate('2026-01-09')
            ->create();
        Payment::factory()->forOrder($otherTenantOrder)->card()->completed()->amount(8000)->create();

        $result = $this->repository->getPaymentMethodStats(
            $this->tenant->id,
            Carbon::parse('2026-01-09'),
            Carbon::parse('2026-01-10')
        );

        $this->assertCount(2, $result);
        $this->assertSame(2, $result['card']['count']);
        $this->assertSame(3000, $result['card']['amount']);
        $this->assertSame(1, $result['paypay']['count']);
        $this->assertSame(500, $result['paypay']['amount']);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\SalesPeriod;
use App\Services\TenantDashboardService;
use App\Services\TenantStatsRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

class TenantDashboardServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-01-20 10:00:00'));
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_get_sales_data_weekly_aggregates_from_single_daily_stats_fetch(): void
    {
        $tenantId = 42;
        $startDate = Carbon::parse('2026-01-08');
        $endDate = Carbon::parse('2026-01-20');
        $expectedRangeStart = $startDate->copy()->startOfWeek(Carbon::MONDAY);
        $secondBucketStart = $expectedRangeStart->copy()->addWeek();
        $thirdBucketStart = $secondBucketStart->copy()->addWeek();
        $dailyStats = [
            '2026-01-05' => ['total_sales' => 100, 'order_count' => 1],
            '2026-01-08' => ['total_sales' => 200, 'order_count' => 2],
            '2026-01-12' => ['total_sales' => 400, 'order_count' => 4],
            '2026-01-20' => ['total_sales' => 500, 'order_count' => 5],
        ];

        $this->mock(TenantStatsRepository::class, function (MockInterface $mock) use ($tenantId, $expectedRangeStart, $endDate, $dailyStats) {
            $mock->shouldReceive('getDailyStatsForRange')
                ->once()
                ->withArgs(function (int $actualTenantId, Carbon $actualStart, Carbon $actualEnd) use ($tenantId, $expectedRangeStart, $endDate): bool {
                    return $actualTenantId === $tenantId
                        && $actualStart->isSameDay($expectedRangeStart)
                        && $actualEnd->isSameDay($endDate);
                })
                ->andReturn($dailyStats);

            $mock->shouldNotReceive('getDateRangeStats');
        });

        $service = app(TenantDashboardService::class);
        $result = $service->getSalesData($tenantId, SalesPeriod::Weekly, $startDate, $endDate);

        $this->assertSame(
            [
                ['date' => $expectedRangeStart->format('Y-m-d'), 'total_sales' => 300, 'order_count' => 3],
                ['date' => $secondBucketStart->format('Y-m-d'), 'total_sales' => 400, 'order_count' => 4],
                ['date' => $thirdBucketStart->format('Y-m-d'), 'total_sales' => 500, 'order_count' => 5],
            ],
            $result
        );
    }

    public function test_get_sales_data_monthly_aggregates_from_single_daily_stats_fetch(): void
    {
        $tenantId = 84;
        $startDate = Carbon::parse('2026-03-10');
        $endDate = Carbon::parse('2026-05-08');
        $expectedRangeStart = Carbon::parse('2026-03-01');
        $dailyStats = [
            '2026-03-01' => ['total_sales' => 120, 'order_count' => 1],
            '2026-03-31' => ['total_sales' => 180, 'order_count' => 2],
            '2026-04-15' => ['total_sales' => 400, 'order_count' => 4],
            '2026-05-01' => ['total_sales' => 200, 'order_count' => 2],
            '2026-05-08' => ['total_sales' => 300, 'order_count' => 3],
        ];

        $this->mock(TenantStatsRepository::class, function (MockInterface $mock) use ($tenantId, $expectedRangeStart, $endDate, $dailyStats) {
            $mock->shouldReceive('getDailyStatsForRange')
                ->once()
                ->withArgs(function (int $actualTenantId, Carbon $actualStart, Carbon $actualEnd) use ($tenantId, $expectedRangeStart, $endDate): bool {
                    return $actualTenantId === $tenantId
                        && $actualStart->isSameDay($expectedRangeStart)
                        && $actualEnd->isSameDay($endDate);
                })
                ->andReturn($dailyStats);

            $mock->shouldNotReceive('getDateRangeStats');
        });

        $service = app(TenantDashboardService::class);
        $result = $service->getSalesData($tenantId, SalesPeriod::Monthly, $startDate, $endDate);

        $this->assertSame(
            [
                ['date' => '2026-03', 'total_sales' => 300, 'order_count' => 3],
                ['date' => '2026-04', 'total_sales' => 400, 'order_count' => 4],
                ['date' => '2026-05', 'total_sales' => 500, 'order_count' => 5],
            ],
            $result
        );
    }

    public function test_get_sales_data_daily_preserves_requested_range_and_zero_fills_missing_days(): void
    {
        $tenantId = 21;
        $startDate = Carbon::parse('2026-01-08');
        $endDate = Carbon::parse('2026-01-10');
        $dailyStats = [
            '2026-01-08' => ['total_sales' => 100, 'order_count' => 1],
            '2026-01-10' => ['total_sales' => 200, 'order_count' => 2],
        ];

        $this->mock(TenantStatsRepository::class, function (MockInterface $mock) use ($tenantId, $startDate, $endDate, $dailyStats) {
            $mock->shouldReceive('getDailyStatsForRange')
                ->once()
                ->withArgs(function (int $actualTenantId, Carbon $actualStart, Carbon $actualEnd) use ($tenantId, $startDate, $endDate): bool {
                    return $actualTenantId === $tenantId
                        && $actualStart->isSameDay($startDate)
                        && $actualEnd->isSameDay($endDate);
                })
                ->andReturn($dailyStats);

            $mock->shouldNotReceive('getDateRangeStats');
        });

        $service = app(TenantDashboardService::class);
        $result = $service->getSalesData($tenantId, SalesPeriod::Daily, $startDate, $endDate);

        $this->assertSame(
            [
                ['date' => '2026-01-08', 'total_sales' => 100, 'order_count' => 1],
                ['date' => '2026-01-09', 'total_sales' => 0, 'order_count' => 0],
                ['date' => '2026-01-10', 'total_sales' => 200, 'order_count' => 2],
            ],
            $result
        );
    }

    public function test_get_top_items_delegates_to_repository_and_formats_response(): void
    {
        $tenantId = 77;
        $period = 'month';
        $limit = 2;
        $expectedStartDate = Carbon::parse('2025-12-21');

        $this->mock(TenantStatsRepository::class, function (MockInterface $mock) use ($tenantId, $expectedStartDate, $limit) {
            $mock->shouldReceive('getTopItems')
                ->once()
                ->withArgs(function (int $actualTenantId, Carbon $actualStartDate, int $actualLimit) use ($tenantId, $expectedStartDate, $limit): bool {
                    return $actualTenantId === $tenantId
                        && $actualStartDate->isSameDay($expectedStartDate)
                        && $actualLimit === $limit;
                })
                ->andReturn([
                    (object) [
                        'menu_item_id' => 10,
                        'name' => 'コーヒー',
                        'total_quantity' => 8,
                        'total_revenue' => 4000,
                    ],
                    (object) [
                        'menu_item_id' => 11,
                        'name' => 'ラテ',
                        'total_quantity' => 3,
                        'total_revenue' => 1800,
                    ],
                ]);
        });

        $service = app(TenantDashboardService::class);
        $result = $service->getTopItems($tenantId, $period, $limit);

        $this->assertSame(
            [
                ['rank' => 1, 'menu_item_id' => 10, 'name' => 'コーヒー', 'quantity' => 8, 'revenue' => 4000],
                ['rank' => 2, 'menu_item_id' => 11, 'name' => 'ラテ', 'quantity' => 3, 'revenue' => 1800],
            ],
            $result
        );
    }

    public function test_get_top_items_resolves_period_to_expected_start_date(): void
    {
        $tenantId = 88;
        $limit = 10;
        $expectedStartDates = [
            'week' => Carbon::parse('2026-01-13'),
            'month' => Carbon::parse('2025-12-21'),
            'year' => Carbon::parse('2025-01-20'),
            'unexpected' => Carbon::parse('2025-12-21'),
        ];

        $this->mock(TenantStatsRepository::class, function (MockInterface $mock) use ($tenantId, $limit, $expectedStartDates) {
            foreach ($expectedStartDates as $period => $expectedStartDate) {
                $mock->shouldReceive('getTopItems')
                    ->once()
                    ->withArgs(function (int $actualTenantId, Carbon $actualStartDate, int $actualLimit) use ($tenantId, $expectedStartDate, $limit): bool {
                        return $actualTenantId === $tenantId
                            && $actualStartDate->isSameDay($expectedStartDate)
                            && $actualLimit === $limit;
                    })
                    ->andReturn([]);
            }
        });

        $service = app(TenantDashboardService::class);

        foreach (array_keys($expectedStartDates) as $period) {
            $service->getTopItems($tenantId, $period, $limit);
        }
    }

    public function test_get_payment_method_stats_delegates_to_repository_and_formats_all_methods(): void
    {
        $tenantId = 51;
        $startDate = Carbon::parse('2026-01-01');
        $endDate = Carbon::parse('2026-01-31');

        $this->mock(TenantStatsRepository::class, function (MockInterface $mock) use ($tenantId, $startDate, $endDate) {
            $mock->shouldReceive('getPaymentMethodStats')
                ->once()
                ->withArgs(function (int $actualTenantId, Carbon $actualStartDate, Carbon $actualEndDate) use ($tenantId, $startDate, $endDate): bool {
                    return $actualTenantId === $tenantId
                        && $actualStartDate->isSameDay($startDate)
                        && $actualEndDate->isSameDay($endDate);
                })
                ->andReturn([
                    'card' => ['count' => 2, 'amount' => 3000],
                ]);
        });

        $service = app(TenantDashboardService::class);
        $result = $service->getPaymentMethodStats($tenantId, $startDate, $endDate);

        $this->assertSame(
            [
                'methods' => [
                    ['method' => 'card', 'label' => 'クレジットカード', 'count' => 2, 'amount' => 3000],
                    ['method' => 'paypay', 'label' => 'PayPay', 'count' => 0, 'amount' => 0],
                ],
                'total_count' => 2,
                'total_amount' => 3000,
            ],
            $result
        );
    }
}

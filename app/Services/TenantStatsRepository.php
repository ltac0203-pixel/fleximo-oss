<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MetricType;
use App\Services\Stats\Queries\DailySalesQuery;
use App\Services\Stats\Queries\DailySalesSeriesQuery;
use App\Services\Stats\Queries\DateRangeSalesQuery;
use App\Services\Stats\Queries\HourlyDistributionQuery;
use App\Services\Stats\Queries\MonthToDateSalesQuery;
use App\Services\Stats\Queries\PaymentMethodBreakdownQuery;
use App\Services\Stats\Queries\TopMenuItemsQuery;
use Carbon\Carbon;

// テナント統計データ取得層のファサード。実際の集計は app/Services/Stats/Queries/ 配下の
// Query Object に委譲する。インスタンス生成時の当日基準を保持し、同一処理中に日付が跨いでも
// 当日判定がブレないことを契約として維持する。
class TenantStatsRepository
{
    // リポジトリ生成時点で当日を凍結する。日付跨ぎ中も一貫した当日判定を担保する。
    private readonly Carbon $resolvedToday;

    public function __construct(
        private readonly AnalyticsService $analyticsService,
        private readonly DailySalesQuery $dailySalesQuery,
        private readonly MonthToDateSalesQuery $monthToDateSalesQuery,
        private readonly DateRangeSalesQuery $dateRangeSalesQuery,
        private readonly DailySalesSeriesQuery $dailySalesSeriesQuery,
        private readonly HourlyDistributionQuery $hourlyDistributionQuery,
        private readonly PaymentMethodBreakdownQuery $paymentMethodBreakdownQuery,
        private readonly TopMenuItemsQuery $topMenuItemsQuery,
    ) {
        $this->resolvedToday = Carbon::today();
    }

    /** @return array{total_sales: int, order_count: int, average_order_value: int} */
    public function getDayStats(int $tenantId, Carbon $date): array
    {
        return $this->dailySalesQuery->forDate($tenantId, $date, $this->today());
    }

    /** @return array{total_sales: int, order_count: int, average_order_value: int} */
    public function getMonthStats(int $tenantId, Carbon $monthStart): array
    {
        $cached = $this->analyticsService->getCachedAnalytics(
            $tenantId,
            MetricType::MonthlySales,
            $monthStart
        );

        if ($cached) {
            return [
                'total_sales' => (int) ($cached['total_sales'] ?? 0),
                'order_count' => (int) ($cached['order_count'] ?? 0),
                'average_order_value' => (int) ($cached['average_order_value'] ?? 0),
            ];
        }

        return ['total_sales' => 0, 'order_count' => 0, 'average_order_value' => 0];
    }

    /** @return array{total_sales: int, order_count: int, average_order_value: int} */
    public function getMonthToDateStats(int $tenantId, Carbon $monthStart, Carbon $today): array
    {
        return $this->monthToDateSalesQuery->forRange($tenantId, $monthStart, $today);
    }

    /** @return array{total_sales: int, order_count: int} */
    public function getDateRangeStats(int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        return $this->dateRangeSalesQuery->forRange($tenantId, $startDate, $endDate, $this->today());
    }

    /** @return array<string, array{total_sales: int, order_count: int, average_order_value: int}> */
    public function getDailyStatsForRange(int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        return $this->dailySalesSeriesQuery->forRange($tenantId, $startDate, $endDate, $this->today());
    }

    /** @return list<object> */
    public function getTopItems(int $tenantId, Carbon $startDate, int $limit): array
    {
        return $this->topMenuItemsQuery->forStartDate($tenantId, $startDate, $limit);
    }

    /** @return array<string, array{count: int, amount: int}> */
    public function getPaymentMethodStats(int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        return $this->paymentMethodBreakdownQuery->forRange($tenantId, $startDate, $endDate);
    }

    /** @return list<array{hour: int, orders: int, sales: int}> */
    public function getHourlyDistribution(int $tenantId, Carbon $date): array
    {
        return $this->hourlyDistributionQuery->forDate($tenantId, $date, $this->today());
    }

    // インスタンス生成時に凍結した当日の複製を返す（呼び出し側で mutate されても影響しないように copy を返す）
    private function today(): Carbon
    {
        return $this->resolvedToday->copy();
    }
}

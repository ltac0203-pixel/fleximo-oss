<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\SalesPeriod;
use App\Enums\TopItemsPeriod;
use App\Services\Dashboard\DashboardCacheKeys;
use App\Services\Dashboard\SalesDataFormatter;
use App\Services\Dashboard\StatsCacheResolver;
use App\Services\Stats\Queries\CustomerInsightsQuery;
use Carbon\Carbon;

class TenantDashboardService
{
    // 本日含む直近7日分を取得するため、6日前を開始日とする
    private const RECENT_WEEK_OFFSET_DAYS = 6;

    public function __construct(
        private readonly TenantStatsRepository $statsRepository,
        private readonly SalesDataFormatter $salesDataFormatter,
        private readonly CustomerInsightsQuery $customerInsightsQuery,
        private readonly StatsCacheResolver $cacheResolver,
    ) {}

    public function getSummary(int $tenantId, Carbon $date): array
    {
        return $this->cacheResolver->rememberForDate(
            DashboardCacheKeys::summary($tenantId, $date),
            $date,
            function () use ($tenantId, $date) {
                $today = $date->copy()->startOfDay();
                $yesterday = $today->copy()->subDay();
                $thisMonthStart = $today->copy()->startOfMonth();
                $lastMonthStart = $thisMonthStart->copy()->subMonth();

                $todayStats = $this->statsRepository->getDayStats($tenantId, $today);
                $yesterdayStats = $this->statsRepository->getDayStats($tenantId, $yesterday);
                $thisMonthStats = $this->statsRepository->getMonthToDateStats($tenantId, $thisMonthStart, $today);
                $lastMonthStats = $this->statsRepository->getMonthStats($tenantId, $lastMonthStart);

                return [
                    'today' => $todayStats,
                    'yesterday' => $yesterdayStats,
                    'this_month' => $thisMonthStats,
                    'last_month' => $lastMonthStats,
                    'comparison' => [
                        'daily_sales_percent' => $this->percentChange(
                            $todayStats['total_sales'],
                            $yesterdayStats['total_sales']
                        ),
                        'daily_orders_percent' => $this->percentChange(
                            $todayStats['order_count'],
                            $yesterdayStats['order_count']
                        ),
                        'monthly_sales_percent' => $this->percentChange(
                            $thisMonthStats['total_sales'],
                            $lastMonthStats['total_sales']
                        ),
                        'monthly_orders_percent' => $this->percentChange(
                            $thisMonthStats['order_count'],
                            $lastMonthStats['order_count']
                        ),
                    ],
                ];
            }
        );
    }

    public function getRecentWeekSalesData(int $tenantId): array
    {
        $today = Carbon::today();

        return $this->cacheResolver->rememberRealtime(
            DashboardCacheKeys::recentWeek($tenantId, $today),
            function () use ($tenantId, $today) {
                $startDate = $today->copy()->subDays(self::RECENT_WEEK_OFFSET_DAYS);

                return $this->getSalesData($tenantId, SalesPeriod::Daily, $startDate, $today);
            }
        );
    }

    public function getSalesData(int $tenantId, SalesPeriod $period, Carbon $startDate, Carbon $endDate): array
    {
        return $this->cacheResolver->rememberForDateRange(
            DashboardCacheKeys::sales($tenantId, $period, $startDate, $endDate),
            $startDate,
            $endDate,
            fn () => $this->buildSalesData($tenantId, $period, $startDate, $endDate)
        );
    }

    private function buildSalesData(int $tenantId, SalesPeriod $period, Carbon $startDate, Carbon $endDate): array
    {
        $fetchStart = match ($period) {
            SalesPeriod::Daily => $startDate,
            SalesPeriod::Weekly => $startDate->copy()->startOfWeek(Carbon::MONDAY),
            SalesPeriod::Monthly => $startDate->copy()->startOfMonth(),
        };

        $dailyStats = $this->statsRepository->getDailyStatsForRange($tenantId, $fetchStart, $endDate);

        return $this->salesDataFormatter->format($period, $fetchStart, $endDate, $dailyStats);
    }

    public function getTopItems(int $tenantId, TopItemsPeriod $period, int $limit = 10): array
    {
        return $this->cacheResolver->rememberRealtime(
            DashboardCacheKeys::topItems($tenantId, $period, $limit),
            function () use ($tenantId, $period, $limit) {
                $startDate = $period->startDate(Carbon::today());
                $items = $this->statsRepository->getTopItems($tenantId, $startDate, $limit);

                $result = [];
                $rank = 1;
                foreach ($items as $item) {
                    $result[] = [
                        'rank' => $rank++,
                        'menu_item_id' => $item->menu_item_id,
                        'name' => $item->name,
                        'quantity' => (int) $item->total_quantity,
                        'revenue' => (int) $item->total_revenue,
                    ];
                }

                return $result;
            }
        );
    }

    public function getHourlyDistribution(int $tenantId, Carbon $date): array
    {
        return $this->cacheResolver->rememberForDate(
            DashboardCacheKeys::hourly($tenantId, $date),
            $date,
            fn () => $this->statsRepository->getHourlyDistribution($tenantId, $date)
        );
    }

    public function getPaymentMethodStats(int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        return $this->cacheResolver->rememberForDateRange(
            DashboardCacheKeys::paymentMethods($tenantId, $startDate, $endDate),
            $startDate,
            $endDate,
            function () use ($tenantId, $startDate, $endDate) {
                $stats = $this->statsRepository->getPaymentMethodStats($tenantId, $startDate, $endDate);

                $methods = [];
                $totalCount = 0;
                $totalAmount = 0;

                foreach (PaymentMethod::cases() as $method) {
                    $data = $stats[$method->value] ?? null;
                    $count = (int) ($data['count'] ?? 0);
                    $amount = (int) ($data['amount'] ?? 0);
                    $methods[] = [
                        'method' => $method->value,
                        'label' => $method->label(),
                        'count' => $count,
                        'amount' => $amount,
                    ];
                    $totalCount += $count;
                    $totalAmount += $amount;
                }

                return [
                    'methods' => $methods,
                    'total_count' => $totalCount,
                    'total_amount' => $totalAmount,
                ];
            }
        );
    }

    public function getCustomerInsights(int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        return $this->cacheResolver->rememberForDateRange(
            DashboardCacheKeys::customerInsights($tenantId, $startDate, $endDate),
            $startDate,
            $endDate,
            fn () => $this->customerInsightsQuery->forRange($tenantId, $startDate, $endDate)
        );
    }

    private function percentChange(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}

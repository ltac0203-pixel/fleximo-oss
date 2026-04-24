<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\SalesPeriod;
use App\Services\Dashboard\DashboardCacheKeys;
use App\Services\Dashboard\SalesDataFormatter;
use App\Services\Dashboard\StatsCacheResolver;
use App\Services\Stats\Queries\CustomerInsightsQuery;
use Carbon\Carbon;

// テナントダッシュボード用データ組み立てサービス
// ダッシュボードAPIのパブリックインターフェースを提供
// 統計データ取得はTenantStatsRepositoryに委譲
// キャッシュキー組み立ては DashboardCacheKeys、TTL 判定は StatsCacheResolver に分離
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

    // サマリーデータを取得
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

    // 直近1週間の売上データを取得
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

    // 期間別売上データを取得
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

    // 人気商品ランキングを取得
    public function getTopItems(int $tenantId, string $period, int $limit = 10): array
    {
        return $this->cacheResolver->rememberRealtime(
            DashboardCacheKeys::topItems($tenantId, $period, $limit),
            function () use ($tenantId, $period, $limit) {
                $today = Carbon::today();
                $startDate = $this->resolveTopItemsStartDate($period, $today);
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

    // 人気商品集計の開始日を期間指定から解決する
    private function resolveTopItemsStartDate(string $period, Carbon $today): Carbon
    {
        return match ($period) {
            'week' => $today->copy()->subDays(7),
            'month' => $today->copy()->subDays(30),
            'year' => $today->copy()->subDays(365),
            default => $today->copy()->subDays(30),
        };
    }

    // 時間帯別分布を取得
    public function getHourlyDistribution(int $tenantId, Carbon $date): array
    {
        return $this->cacheResolver->rememberForDate(
            DashboardCacheKeys::hourly($tenantId, $date),
            $date,
            fn () => $this->statsRepository->getHourlyDistribution($tenantId, $date)
        );
    }

    // 決済方法別統計を取得
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

    // 顧客分析データを取得
    public function getCustomerInsights(int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        return $this->cacheResolver->rememberForDateRange(
            DashboardCacheKeys::customerInsights($tenantId, $startDate, $endDate),
            $startDate,
            $endDate,
            fn () => $this->customerInsightsQuery->forRange($tenantId, $startDate, $endDate)
        );
    }

    // 変化率を計算する
    private function percentChange(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}

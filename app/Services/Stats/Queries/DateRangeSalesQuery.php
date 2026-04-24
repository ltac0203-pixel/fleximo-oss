<?php

declare(strict_types=1);

namespace App\Services\Stats\Queries;

use App\Enums\MetricType;
use App\Models\AnalyticsCache;
use App\Services\Stats\Concerns\FillsMissingDates;
use Carbon\Carbon;

// 任意期間の売上合計 (単一配列) を取得。過去日はキャッシュ + 欠損補完、
// 期間が当日を含む場合は当日分をリアルタイム合算する。
class DateRangeSalesQuery
{
    use FillsMissingDates;

    public function __construct(private readonly DailySalesQuery $dailySalesQuery) {}

    /**
     * @return array{total_sales: int, order_count: int}
     */
    public function forRange(int $tenantId, Carbon $startDate, Carbon $endDate, Carbon $today): array
    {
        $totalSales = 0;
        $totalOrders = 0;

        // 確定済みの過去日はキャッシュから一括取得し、Ordersテーブルへの直接クエリを避ける
        $cacheEnd = $endDate->lt($today) ? $endDate : $today->copy()->subDay();
        $cachedDates = [];
        if ($startDate->lte($cacheEnd)) {
            $cursor = AnalyticsCache::forTenant($tenantId)
                ->ofType(MetricType::DailySales)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $cacheEnd->format('Y-m-d')])
                ->orderBy('id')
                ->cursor();

            foreach ($cursor as $cache) {
                $totalSales += (int) ($cache->data['total_sales'] ?? 0);
                $totalOrders += (int) ($cache->data['order_count'] ?? 0);
                $cachedDates[] = $cache->date->format('Y-m-d');
            }

            // バッチ未実行等でキャッシュが欠損している日はDBから直接補完する
            $fallback = $this->aggregateSalesByDates(
                $tenantId,
                $this->findMissingDates($startDate, $cacheEnd, $cachedDates)
            );
            $totalSales += $fallback['total_sales'];
            $totalOrders += $fallback['order_count'];
        }

        // 当日分は注文が随時追加されるため、キャッシュではなくDBから最新値を取得する
        if ($today->between($startDate, $endDate)) {
            $todayStats = $this->dailySalesQuery->aggregateRealtime($tenantId, $today);
            $totalSales += $todayStats['total_sales'];
            $totalOrders += $todayStats['order_count'];
        }

        return [
            'total_sales' => $totalSales,
            'order_count' => $totalOrders,
        ];
    }
}

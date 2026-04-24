<?php

declare(strict_types=1);

namespace App\Services\Stats\Queries;

use App\Enums\MetricType;
use App\Models\AnalyticsCache;
use App\Services\Stats\Concerns\FillsMissingDates;
use Carbon\Carbon;

// 月初〜当日の累計を取得。確定済みの過去日は AnalyticsCache から、
// キャッシュ欠損日は DB から直接補完し、当日分はリアルタイム集計して合算する。
class MonthToDateSalesQuery
{
    use FillsMissingDates;

    public function __construct(private readonly DailySalesQuery $dailySalesQuery) {}

    /**
     * @return array{total_sales: int, order_count: int, average_order_value: int}
     */
    public function forRange(int $tenantId, Carbon $monthStart, Carbon $today): array
    {
        $totalSales = 0;
        $totalOrders = 0;

        // 確定済みの過去日はキャッシュを逐次処理で取得し、長期間でもメモリ使用量を抑える
        $cacheEnd = $today->copy()->subDay();
        $cachedDates = [];
        if ($monthStart->lte($cacheEnd)) {
            $cursor = AnalyticsCache::forTenant($tenantId)
                ->ofType(MetricType::DailySales)
                ->whereBetween('date', [$monthStart->format('Y-m-d'), $cacheEnd->format('Y-m-d')])
                ->orderBy('id')
                ->cursor();

            foreach ($cursor as $cache) {
                $data = $cache->data;
                $totalSales += (int) ($data['total_sales'] ?? 0);
                $totalOrders += (int) ($data['order_count'] ?? 0);
                $cachedDates[] = $cache->date->format('Y-m-d');
            }

            // バッチ未実行等でキャッシュが欠損している日はDBから直接補完する
            $fallback = $this->aggregateSalesByDates(
                $tenantId,
                $this->findMissingDates($monthStart, $cacheEnd, $cachedDates)
            );
            $totalSales += $fallback['total_sales'];
            $totalOrders += $fallback['order_count'];
        }

        // 当日分はまだ確定していないため、キャッシュではなくDBからリアルタイム取得する
        $todayStats = $this->dailySalesQuery->aggregateRealtime($tenantId, $today);
        $totalSales += $todayStats['total_sales'];
        $totalOrders += $todayStats['order_count'];

        return [
            'total_sales' => $totalSales,
            'order_count' => $totalOrders,
            'average_order_value' => $this->averageOrderValue($totalSales, $totalOrders),
        ];
    }
}

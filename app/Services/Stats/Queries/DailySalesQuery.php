<?php

declare(strict_types=1);

namespace App\Services\Stats\Queries;

use App\Enums\MetricType;
use App\Enums\OrderStatus;
use App\Models\AnalyticsCache;
use App\Models\Order;
use App\Models\Scopes\TenantScope;
use App\Services\Stats\Concerns\FillsMissingDates;
use Carbon\Carbon;

// 日次売上 Query。当日は orders テーブルへのリアルタイム集計、
// 過去日は analytics_cache の DailySales エントリを参照する。
class DailySalesQuery
{
    use FillsMissingDates;

    /**
     * @return array{total_sales: int, order_count: int, average_order_value: int}
     */
    public function forDate(int $tenantId, Carbon $date, Carbon $today): array
    {
        if ($date->isSameDay($today)) {
            return $this->aggregateRealtime($tenantId, $date);
        }

        return $this->fetchFromCache($tenantId, $date);
    }

    /**
     * @return array{total_sales: int, order_count: int, average_order_value: int}
     */
    public function aggregateRealtime(int $tenantId, Carbon $date): array
    {
        $stats = Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereDate('business_date', $date)
            ->whereIn('status', OrderStatus::salesStatusValues())
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sales')
            ->first();

        $orderCount = (int) $stats->order_count;
        $totalSales = (int) $stats->total_sales;

        return [
            'total_sales' => $totalSales,
            'order_count' => $orderCount,
            'average_order_value' => $this->averageOrderValue($totalSales, $orderCount),
        ];
    }

    /**
     * @return array{total_sales: int, order_count: int, average_order_value: int}
     */
    private function fetchFromCache(int $tenantId, Carbon $date): array
    {
        $cached = AnalyticsCache::getCached($tenantId, MetricType::DailySales, $date);

        if ($cached) {
            return [
                'total_sales' => (int) ($cached['total_sales'] ?? 0),
                'order_count' => (int) ($cached['order_count'] ?? 0),
                'average_order_value' => (int) ($cached['average_order_value'] ?? 0),
            ];
        }

        return ['total_sales' => 0, 'order_count' => 0, 'average_order_value' => 0];
    }
}

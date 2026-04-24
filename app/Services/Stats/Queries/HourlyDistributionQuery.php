<?php

declare(strict_types=1);

namespace App\Services\Stats\Queries;

use App\Enums\MetricType;
use App\Enums\OrderStatus;
use App\Models\AnalyticsCache;
use App\Models\Order;
use App\Models\Scopes\TenantScope;
use Carbon\Carbon;

// 1 日の時間帯別分布。当日はリアルタイム集計、過去日はキャッシュ (HourlyDistribution メトリクス) を読み、
// キャッシュ未生成時は DB 直集計にフォールバックする。24 時間分 0 埋めを内包。
class HourlyDistributionQuery
{
    /**
     * @return list<array{hour: int, orders: int, sales: int}>
     */
    public function forDate(int $tenantId, Carbon $date, Carbon $today): array
    {
        if ($date->isSameDay($today)) {
            return $this->aggregateRealtime($tenantId, $date);
        }

        // 確定済みの過去日はキャッシュから取得し、集計クエリのコストを回避する
        $cached = AnalyticsCache::getCached($tenantId, MetricType::HourlyDistribution, $date);
        if ($cached && isset($cached['hourly_stats'])) {
            return $cached['hourly_stats'];
        }

        // キャッシュ未生成の場合（バッチ未実行等）はフォールバックとしてDB直接集計する
        return $this->aggregateRealtime($tenantId, $date);
    }

    /**
     * @return list<array{hour: int, orders: int, sales: int}>
     */
    private function aggregateRealtime(int $tenantId, Carbon $date): array
    {
        $hourlyData = Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereDate('business_date', $date)
            ->whereIn('status', OrderStatus::salesStatusValues())
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as sales')
            ->groupByRaw('HOUR(created_at)')
            ->get()
            ->keyBy('hour');

        $result = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $result[] = [
                'hour' => $hour,
                'orders' => (int) ($hourlyData[$hour]->orders ?? 0),
                'sales' => (int) ($hourlyData[$hour]->sales ?? 0),
            ];
        }

        return $result;
    }
}

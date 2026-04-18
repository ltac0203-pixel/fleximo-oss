<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MetricType;
use App\Enums\OrderStatus;
use App\Models\AnalyticsCache;
use App\Models\Order;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Carbon\Carbon;

class PlatformRevenueDashboardService
{
    public function buildDashboard(Carbon $startDate, Carbon $endDate, int $rankingLimit = 10): array
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->startOfDay();

        $tenantTotals = [];
        $dailyTrend = [];
        $dailyBreakdowns = [];
        $gmvTotal = 0;
        $orderCountTotal = 0;

        $current = $start->copy();
        while ($current->lte($end)) {
            $daily = $this->resolveDailySales($current);
            $dateKey = $current->toDateString();

            $dailyBreakdowns[$dateKey] = $daily['tenant_breakdown'];
            $dailyTrend[] = [
                'date' => $dateKey,
                'gmv' => $daily['total_sales'],
                'order_count' => $daily['order_count'],
                'estimated_fee' => 0,
            ];

            $gmvTotal += $daily['total_sales'];
            $orderCountTotal += $daily['order_count'];

            foreach ($daily['tenant_breakdown'] as $row) {
                $tenantId = (int) $row['tenant_id'];
                if (! isset($tenantTotals[$tenantId])) {
                    $tenantTotals[$tenantId] = [
                        'gmv' => 0,
                        'order_count' => 0,
                    ];
                }

                $tenantTotals[$tenantId]['gmv'] += (int) $row['sales'];
                $tenantTotals[$tenantId]['order_count'] += (int) $row['count'];
            }

            $current->addDay();
        }

        $tenantMeta = $this->loadTenantMeta(array_keys($tenantTotals));
        $defaultFeeRateBps = $this->defaultFeeRateBps();

        $estimatedFeeTotal = 0;
        $ranking = [];
        foreach ($tenantTotals as $tenantId => $data) {
            $tenant = $tenantMeta[$tenantId] ?? null;
            $feeRateBps = $tenant['fee_rate_bps'] ?? $defaultFeeRateBps;
            $estimatedFee = $this->calculateFee($data['gmv'], $feeRateBps);
            $estimatedFeeTotal += $estimatedFee;

            $ranking[] = [
                'tenant_id' => $tenantId,
                'tenant_name' => $tenant['name'] ?? "Tenant #{$tenantId}",
                'gmv' => $data['gmv'],
                'order_count' => $data['order_count'],
                'estimated_fee' => $estimatedFee,
                'fee_rate_bps' => $feeRateBps,
                'share_percent' => $gmvTotal > 0 ? round(($data['gmv'] / $gmvTotal) * 100, 1) : 0.0,
            ];
        }

        usort(
            $ranking,
            fn (array $a, array $b) => $b['gmv'] <=> $a['gmv'] ?: $a['tenant_id'] <=> $b['tenant_id']
        );
        $ranking = array_slice($ranking, 0, $rankingLimit);

        foreach ($dailyTrend as $index => $item) {
            $dateBreakdown = $dailyBreakdowns[$item['date']] ?? [];
            $estimatedFee = 0;

            foreach ($dateBreakdown as $row) {
                $tenantId = (int) $row['tenant_id'];
                $feeRateBps = $tenantMeta[$tenantId]['fee_rate_bps'] ?? $defaultFeeRateBps;
                $estimatedFee += $this->calculateFee((int) $row['sales'], $feeRateBps);
            }

            $dailyTrend[$index]['estimated_fee'] = $estimatedFee;
        }

        return [
            'overview' => [
                'gmv_total' => $gmvTotal,
                'order_count_total' => $orderCountTotal,
                'avg_order_value' => $orderCountTotal > 0 ? (int) round($gmvTotal / $orderCountTotal) : 0,
                'estimated_fee_total' => $estimatedFeeTotal,
                'active_tenant_count' => count($tenantTotals),
            ],
            'trend' => $dailyTrend,
            'ranking' => $ranking,
        ];
    }

    private function resolveDailySales(Carbon $date): array
    {
        $cached = AnalyticsCache::forPlatform()
            ->ofType(MetricType::DailySales)
            ->forDate($date)
            ->first();

        if ($cached === null || ! is_array($cached->data)) {
            return $this->aggregateDailySalesDirect($date);
        }

        return $this->normalizeDailyData($cached->data);
    }

    private function normalizeDailyData(array $data): array
    {
        $breakdown = [];
        foreach (($data['tenant_breakdown'] ?? []) as $row) {
            if (! isset($row['tenant_id'])) {
                continue;
            }

            $breakdown[] = [
                'tenant_id' => (int) $row['tenant_id'],
                'sales' => (int) ($row['sales'] ?? 0),
                'count' => (int) ($row['count'] ?? 0),
            ];
        }

        return [
            'total_sales' => (int) ($data['total_sales'] ?? 0),
            'order_count' => (int) ($data['order_count'] ?? 0),
            'tenant_breakdown' => $breakdown,
        ];
    }

    private function aggregateDailySalesDirect(Carbon $date): array
    {
        $rows = Order::withoutGlobalScope(TenantScope::class)
            ->whereDate('business_date', $date->toDateString())
            ->whereIn('status', OrderStatus::salesStatuses())
            ->selectRaw('tenant_id, COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_sales')
            ->groupBy('tenant_id')
            ->get();

        $totalSales = 0;
        $orderCount = 0;
        $breakdown = [];

        foreach ($rows as $row) {
            $sales = (int) $row->total_sales;
            $count = (int) $row->order_count;
            $totalSales += $sales;
            $orderCount += $count;

            $breakdown[] = [
                'tenant_id' => (int) $row->tenant_id,
                'sales' => $sales,
                'count' => $count,
            ];
        }

        return [
            'total_sales' => $totalSales,
            'order_count' => $orderCount,
            'tenant_breakdown' => $breakdown,
        ];
    }

    /**
     * @param  int[]  $tenantIds
     * @return array<int, array{name: string, fee_rate_bps: int}>
     */
    private function loadTenantMeta(array $tenantIds): array
    {
        if ($tenantIds === []) {
            return [];
        }

        $defaultFeeRateBps = $this->defaultFeeRateBps();

        return Tenant::query()
            ->whereIn('id', $tenantIds)
            ->get(['id', 'name', 'platform_fee_rate_bps'])
            ->mapWithKeys(function (Tenant $tenant) use ($defaultFeeRateBps) {
                return [
                    $tenant->id => [
                        'name' => $tenant->name,
                        'fee_rate_bps' => $tenant->platform_fee_rate_bps ?? $defaultFeeRateBps,
                    ],
                ];
            })
            ->all();
    }

    private function defaultFeeRateBps(): int
    {
        return (int) config('platform.default_fee_rate_bps', 600);
    }

    private function calculateFee(int $gmv, int $feeRateBps): int
    {
        return (int) round($gmv * $feeRateBps / 10000);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MetricType;
use App\Enums\OrderStatus;
use App\Exceptions\TenantDailyAnalyticsPartialFailureException;
use App\Jobs\AggregateDailyAnalyticsJob;
use App\Models\AnalyticsCache;
use App\Models\HourlyOrderStat;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyticsService
{
    // 日次売上を集計する
    public function aggregateDailySales(int $tenantId, Carbon $date): array
    {
        $result = Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereDate('business_date', $date)
            ->whereIn('status', OrderStatus::salesStatuses())
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_sales')
            ->first();

        $orderCount = (int) $result->order_count;
        $totalSales = (int) $result->total_sales;
        $averageOrderValue = $orderCount > 0 ? (int) round($totalSales / $orderCount) : 0;

        return [
            'total_sales' => $totalSales,
            'order_count' => $orderCount,
            'average_order_value' => $averageOrderValue,
        ];
    }

    // 月次売上を集計する（日別推移を含む）
    public function aggregateMonthlySales(int $tenantId, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $dailyResults = Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereBetween('business_date', [$startDate, $endDate])
            ->whereIn('status', OrderStatus::salesStatuses())
            ->selectRaw('DATE(business_date) as date, COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_sales')
            ->groupBy(DB::raw('DATE(business_date)'))
            ->get();

        $totalSales = 0;
        $totalOrderCount = 0;
        $dailyBreakdown = [];

        foreach ($dailyResults as $row) {
            $sales = (int) $row->total_sales;
            $count = (int) $row->order_count;
            $totalSales += $sales;
            $totalOrderCount += $count;
            $dailyBreakdown[$row->date] = [
                'sales' => $sales,
                'count' => $count,
            ];
        }

        $averageOrderValue = $totalOrderCount > 0 ? (int) round($totalSales / $totalOrderCount) : 0;

        return [
            'total_sales' => $totalSales,
            'order_count' => $totalOrderCount,
            'average_order_value' => $averageOrderValue,
            'daily_breakdown' => $dailyBreakdown,
        ];
    }

    // 日次注文統計を集計する（ステータス別注文数）
    public function aggregateDailyOrderStats(int $tenantId, Carbon $date): array
    {
        $results = Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereDate('business_date', $date)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // フロント側で全ステータスを表示するため、注文のない時間帯でも0を埋めて返す
        $stats = [];
        foreach (OrderStatus::values() as $status) {
            $stats[$status] = $results[$status] ?? 0;
        }

        return $stats;
    }

    // 人気商品ランキングを集計する
    public function aggregateTopMenuItems(int $tenantId, int $year, int $month, int $limit = 10): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $results = OrderItem::withoutGlobalScope(TenantScope::class)
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.business_date', [$startDate, $endDate])
            ->whereIn('orders.status', OrderStatus::salesStatuses())
            ->selectRaw('order_items.menu_item_id, menu_items.name, SUM(order_items.quantity) as total_quantity, SUM(order_items.quantity * order_items.price) as total_amount')
            ->groupBy('order_items.menu_item_id', 'menu_items.name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();

        return $results->map(function ($row) {
            return [
                'menu_item_id' => (int) $row->menu_item_id,
                'name' => $row->name,
                'quantity' => (int) $row->total_quantity,
                'total_amount' => (int) $row->total_amount,
            ];
        })->toArray();
    }

    // 時間帯別分布を集計する
    public function aggregateHourlyDistribution(int $tenantId, Carbon $date): array
    {
        $results = Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereDate('business_date', $date)
            ->whereIn('status', OrderStatus::salesStatuses())
            ->selectRaw('HOUR(paid_at) as hour, COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_amount')
            ->whereNotNull('paid_at')
            ->groupBy(DB::raw('HOUR(paid_at)'))
            ->orderBy('hour')
            ->get();

        // フロントのグラフ表示で全時間帯を連続して描画するため、注文のない時間帯もゼロ値で埋めておく
        $hourlyData = [];
        for ($h = 0; $h < 24; $h++) {
            $hourlyData[$h] = [
                'hour' => $h,
                'order_count' => 0,
                'total_amount' => 0,
            ];
        }

        // DBから取得した実データで初期化済み配列を上書きし、欠損時間帯はゼロのまま維持する
        foreach ($results as $row) {
            if ($row->hour !== null) {
                $hourlyData[(int) $row->hour] = [
                    'hour' => (int) $row->hour,
                    'order_count' => (int) $row->order_count,
                    'total_amount' => (int) $row->total_amount,
                ];
            }
        }

        return array_values($hourlyData);
    }

    // プラットフォーム全体の日次売上を集計する
    public function aggregatePlatformDailySales(Carbon $date): array
    {
        $results = Order::withoutGlobalScope(TenantScope::class)
            ->whereDate('business_date', $date)
            ->whereIn('status', OrderStatus::salesStatuses())
            ->selectRaw('tenant_id, COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_sales')
            ->groupBy('tenant_id')
            ->get();

        $totalSales = 0;
        $totalOrderCount = 0;
        $tenantBreakdown = [];

        foreach ($results as $row) {
            $sales = (int) $row->total_sales;
            $count = (int) $row->order_count;
            $totalSales += $sales;
            $totalOrderCount += $count;
            $tenantBreakdown[] = [
                'tenant_id' => (int) $row->tenant_id,
                'sales' => $sales,
                'count' => $count,
            ];
        }

        $averageOrderValue = $totalOrderCount > 0 ? (int) round($totalSales / $totalOrderCount) : 0;

        return [
            'total_sales' => $totalSales,
            'order_count' => $totalOrderCount,
            'average_order_value' => $averageOrderValue,
            'tenant_breakdown' => $tenantBreakdown,
        ];
    }

    // HourlyOrderStatを更新する
    public function updateHourlyStats(int $tenantId, Carbon $date): void
    {
        $hourlyData = $this->aggregateHourlyDistribution($tenantId, $date);
        HourlyOrderStat::upsertBatch($tenantId, $date, $hourlyData);
    }

    // キャッシュを保存する
    public function saveCache(
        ?int $tenantId,
        MetricType $metricType,
        Carbon $date,
        array $data
    ): AnalyticsCache {
        return AnalyticsCache::saveCache($tenantId, $metricType, $date, $data);
    }

    public function getCachedAnalytics(
        ?int $tenantId,
        MetricType $metricType,
        Carbon $date
    ): ?array {
        return AnalyticsCache::getCached($tenantId, $metricType, $date);
    }

    // 全テナントの日次分析を集計する
    public function aggregateAllTenantsDailyAnalytics(Carbon $date): void
    {
        $tenantFailures = [];

        // テナント数増加時のメモリ枯渇を防ぐため、チャンクごとに集計して即時保存する
        Tenant::query()->select('id')->chunkById(100, function ($tenants) use ($date, &$tenantFailures) {
            $chunkCacheEntries = [];

            foreach ($tenants as $tenant) {
                try {
                    $tenantCacheEntries = $this->aggregateTenantDailyAnalyticsBatch($tenant->id, $date);
                    array_push($chunkCacheEntries, ...$tenantCacheEntries);
                } catch (Throwable $e) {
                    $tenantFailures[] = [
                        'tenant_id' => $tenant->id,
                        'error' => $e->getMessage(),
                        'exception_class' => $e::class,
                    ];

                    Log::error('Failed to aggregate daily analytics for tenant', [
                        'date' => $date->toDateString(),
                        'tenant_id' => $tenant->id,
                        'error' => $e->getMessage(),
                        'exception_class' => $e::class,
                    ]);
                }
            }

            // 個別保存だとテナント数分のINSERTが走るため、チャンク単位でバッチ保存してDB負荷を抑える
            if ($chunkCacheEntries !== []) {
                AnalyticsCache::saveCacheBatch($chunkCacheEntries);
            }
        });

        // テナント横断の売上を管理者ダッシュボードで表示するため、プラットフォーム全体の集計も行う
        $this->savePlatformDailySales($date);

        if ($tenantFailures !== []) {
            throw TenantDailyAnalyticsPartialFailureException::fromFailures($date, $tenantFailures);
        }
    }

    // 全テナントの日次分析ジョブをテナント単位で分散実行する
    public function dispatchAllTenantsDailyAnalytics(Carbon $date): int
    {
        $dispatchedCount = 0;

        Tenant::query()->select('id')->chunkById(100, function ($tenants) use ($date, &$dispatchedCount) {
            foreach ($tenants as $tenant) {
                AggregateDailyAnalyticsJob::dispatch($date, $tenant->id);
                $dispatchedCount++;
            }
        });

        return $dispatchedCount;
    }

    // プラットフォーム全体の日次売上キャッシュを保存する
    public function savePlatformDailySales(Carbon $date): void
    {
        $platformDailySales = $this->aggregatePlatformDailySales($date);

        AnalyticsCache::saveCacheBatch([
            [
                'tenant_id' => null,
                'metric_type' => MetricType::DailySales,
                'date' => $date,
                'data' => $platformDailySales,
            ],
        ]);
    }

    // 特定テナントの日次分析を集計する（キャッシュエントリを返す）
    protected function aggregateTenantDailyAnalyticsBatch(int $tenantId, Carbon $date): array
    {
        $cacheEntries = [];

        // 売上・注文統計・時間帯分布の3種をまとめて集計し、バッチ保存に備えてキャッシュエントリを構築する
        $dailySales = $this->aggregateDailySales($tenantId, $date);
        $cacheEntries[] = [
            'tenant_id' => $tenantId,
            'metric_type' => MetricType::DailySales,
            'date' => $date,
            'data' => $dailySales,
        ];

        $dailyOrderStats = $this->aggregateDailyOrderStats($tenantId, $date);
        $cacheEntries[] = [
            'tenant_id' => $tenantId,
            'metric_type' => MetricType::DailyOrderStats,
            'date' => $date,
            'data' => $dailyOrderStats,
        ];

        $hourlyDistribution = $this->aggregateHourlyDistribution($tenantId, $date);
        $cacheEntries[] = [
            'tenant_id' => $tenantId,
            'metric_type' => MetricType::HourlyDistribution,
            'date' => $date,
            'data' => $hourlyDistribution,
        ];

        // キャッシュとは別に、KDSやリアルタイム表示で使う専用テーブルにも反映する
        HourlyOrderStat::upsertBatch($tenantId, $date, $hourlyDistribution);

        return $cacheEntries;
    }

    // 特定テナントの日次分析を集計する
    public function aggregateTenantDailyAnalytics(int $tenantId, Carbon $date): void
    {
        $cacheEntries = $this->aggregateTenantDailyAnalyticsBatch($tenantId, $date);
        AnalyticsCache::saveCacheBatch($cacheEntries);
    }

    // 全テナントの月次分析を集計する
    public function aggregateAllTenantsMonthlyAnalytics(int $year, int $month): void
    {
        Tenant::query()->select('id')->chunkById(100, function ($tenants) use ($year, $month) {
            $chunkCacheEntries = [];
            foreach ($tenants as $tenant) {
                $tenantCacheEntries = $this->aggregateTenantMonthlyAnalyticsBatch($tenant->id, $year, $month);
                array_push($chunkCacheEntries, ...$tenantCacheEntries);
            }
            AnalyticsCache::saveCacheBatch($chunkCacheEntries);
        });
    }

    // 特定テナントの月次分析を集計する
    public function aggregateTenantMonthlyAnalytics(int $tenantId, int $year, int $month): void
    {
        $cacheEntries = $this->aggregateTenantMonthlyAnalyticsBatch($tenantId, $year, $month);
        AnalyticsCache::saveCacheBatch($cacheEntries);
    }

    // 月次集計のキャッシュエントリを構築する（保存は呼び出し元が行う）
    private function aggregateTenantMonthlyAnalyticsBatch(int $tenantId, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1);
        $cacheEntries = [];

        $monthlySales = $this->aggregateMonthlySales($tenantId, $year, $month);
        $cacheEntries[] = [
            'tenant_id' => $tenantId,
            'metric_type' => MetricType::MonthlySales,
            'date' => $startDate,
            'data' => $monthlySales,
        ];

        $topMenuItems = $this->aggregateTopMenuItems($tenantId, $year, $month);
        $cacheEntries[] = [
            'tenant_id' => $tenantId,
            'metric_type' => MetricType::TopMenuItems,
            'date' => $startDate,
            'data' => $topMenuItems,
        ];

        return $cacheEntries;
    }
}

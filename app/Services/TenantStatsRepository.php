<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MetricType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\AnalyticsCache;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use Carbon\Carbon;

// テナント統計データ取得層
// リアルタイム/キャッシュの判断を含む統計データ取得を担当
class TenantStatsRepository
{
    private readonly Carbon $resolvedToday;

    public function __construct(
        private readonly AnalyticsService $analyticsService
    ) {
        $this->resolvedToday = Carbon::today();
    }

    // 日次統計を取得（今日: リアルタイム、過去: キャッシュ）
    public function getDayStats(int $tenantId, Carbon $date): array
    {
        if ($date->isSameDay($this->todayReference())) {
            return $this->queryRealtimeStats($tenantId, $date);
        }

        return $this->getCachedDayStats($tenantId, $date);
    }

    // 月次統計を取得（キャッシュから）
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

    // 今月累計を取得（過去日キャッシュ + 今日リアルタイム）
    public function getMonthToDateStats(int $tenantId, Carbon $monthStart, Carbon $today): array
    {
        $totalSales = 0;
        $totalOrders = 0;

        // 確定済みの過去日はキャッシュを逐次処理で取得し、長期間でもメモリ使用量を抑える
        $cacheEnd = $today->copy()->subDay();
        $cachedDates = [];
        if ($monthStart->lte($cacheEnd)) {
            foreach ($this->dailySalesCacheCursor($tenantId, $monthStart, $cacheEnd) as $cache) {
                $data = $cache->data;
                $totalSales += (int) ($data['total_sales'] ?? 0);
                $totalOrders += (int) ($data['order_count'] ?? 0);
                $cachedDates[] = $cache->date->format('Y-m-d');
            }

            // バッチ未実行等でキャッシュが欠損している日はDBから直接補完する
            $missingDates = $this->findMissingDates($monthStart, $cacheEnd, $cachedDates);
            if ($missingDates !== []) {
                $fallback = $this->queryTotalStatsByDates($tenantId, $missingDates);
                $totalSales += $fallback['total_sales'];
                $totalOrders += $fallback['order_count'];
            }
        }

        // 当日分はまだ確定していないため、キャッシュではなくDBからリアルタイム取得する
        $todayStats = $this->queryRealtimeStats($tenantId, $today);
        $totalSales += $todayStats['total_sales'];
        $totalOrders += $todayStats['order_count'];

        $average = $totalOrders > 0 ? (int) round($totalSales / $totalOrders) : 0;

        return [
            'total_sales' => $totalSales,
            'order_count' => $totalOrders,
            'average_order_value' => $average,
        ];
    }

    // 任意期間の合計を取得
    public function getDateRangeStats(int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        $totalSales = 0;
        $totalOrders = 0;
        $today = $this->todayReference();

        // 確定済みの過去日はキャッシュから一括取得し、Ordersテーブルへの直接クエリを避ける
        $cacheEnd = $endDate->lt($today) ? $endDate : $today->copy()->subDay();
        $cachedDates = [];
        if ($startDate->lte($cacheEnd)) {
            foreach ($this->dailySalesCacheCursor($tenantId, $startDate, $cacheEnd) as $cache) {
                $totalSales += (int) ($cache->data['total_sales'] ?? 0);
                $totalOrders += (int) ($cache->data['order_count'] ?? 0);
                $cachedDates[] = $cache->date->format('Y-m-d');
            }

            // バッチ未実行等でキャッシュが欠損している日はDBから直接補完する
            $missingDates = $this->findMissingDates($startDate, $cacheEnd, $cachedDates);
            if ($missingDates !== []) {
                $fallback = $this->queryTotalStatsByDates($tenantId, $missingDates);
                $totalSales += $fallback['total_sales'];
                $totalOrders += $fallback['order_count'];
            }
        }

        // 当日分は注文が随時追加されるため、キャッシュではなくDBから最新値を取得する
        if ($today->between($startDate, $endDate)) {
            $todayStats = $this->queryRealtimeStats($tenantId, $today);
            $totalSales += $todayStats['total_sales'];
            $totalOrders += $todayStats['order_count'];
        }

        return [
            'total_sales' => $totalSales,
            'order_count' => $totalOrders,
        ];
    }

    // 日付範囲の日次統計を一括取得（日次売上データ表示用）
    // キーは日付文字列（Y-m-d形式）
    public function getDailyStatsForRange(int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        $result = [];
        $today = $this->todayReference();

        // キャッシュに存在しない日付（注文ゼロの日）もグラフ表示に必要なため、全日付を0で初期化する
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dateKey = $current->format('Y-m-d');
            $result[$dateKey] = [
                'total_sales' => 0,
                'order_count' => 0,
                'average_order_value' => 0,
            ];
            $current->addDay();
        }

        // 確定済みの過去日はキャッシュから一括取得し、日数分のクエリ発行を回避する
        $cacheEnd = $endDate->lt($today) ? $endDate : $today->copy()->subDay();
        $cachedDates = [];
        if ($startDate->lte($cacheEnd)) {
            foreach ($this->dailySalesCacheCursor($tenantId, $startDate, $cacheEnd) as $cache) {
                $dateKey = $cache->date->format('Y-m-d');
                $result[$dateKey] = [
                    'total_sales' => (int) ($cache->data['total_sales'] ?? 0),
                    'order_count' => (int) ($cache->data['order_count'] ?? 0),
                    'average_order_value' => (int) ($cache->data['average_order_value'] ?? 0),
                ];
                $cachedDates[] = $dateKey;
            }

            // バッチ未実行等でキャッシュが欠損している日はDBから直接補完する
            $missingDates = $this->findMissingDates($startDate, $cacheEnd, $cachedDates);
            if ($missingDates !== []) {
                foreach ($this->queryDailyStatsByDates($tenantId, $missingDates) as $dateKey => $stats) {
                    $result[$dateKey] = $stats;
                }
            }
        }

        // 当日分は注文が随時追加されるため、キャッシュではなくDBから最新値を取得する
        if ($today->between($startDate, $endDate)) {
            $todayStats = $this->queryRealtimeStats($tenantId, $today);
            $result[$today->format('Y-m-d')] = $todayStats;
        }

        return $result;
    }

    public function getTopItems(int $tenantId, Carbon $startDate, int $limit): array
    {
        return OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->where('orders.tenant_id', $tenantId)
            ->where('orders.business_date', '>=', $startDate->format('Y-m-d'))
            ->whereIn('orders.status', OrderStatus::salesStatusValues())
            ->select('order_items.menu_item_id', 'menu_items.name')
            ->selectRaw('SUM(order_items.quantity) as total_quantity')
            ->selectRaw('SUM(order_items.price * order_items.quantity) as total_revenue')
            ->groupBy('order_items.menu_item_id', 'menu_items.name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getPaymentMethodStats(int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        return Payment::withoutGlobalScope(TenantScope::class)
            ->where('payments.tenant_id', $tenantId)
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->whereBetween('orders.business_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where('payments.status', PaymentStatus::Completed->value)
            ->select('payments.method')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as amount')
            ->groupBy('payments.method')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                ($row->method instanceof PaymentMethod ? $row->method->value : (string) $row->method) => [
                    'count' => (int) $row->count,
                    'amount' => (int) $row->amount,
                ],
            ])
            ->all();
    }

    // 指定期間のうちキャッシュが存在しない日付の配列を返す
    private function findMissingDates(Carbon $start, Carbon $end, array $cachedDates): array
    {
        $missing = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $dateKey = $current->format('Y-m-d');
            if (! in_array($dateKey, $cachedDates, true)) {
                $missing[] = $dateKey;
            }
            $current->addDay();
        }

        return $missing;
    }

    // 欠損日リストをまとめてDBで集計（whereIn で N+1 を回避）
    private function queryTotalStatsByDates(int $tenantId, array $dates): array
    {
        if ($dates === []) {
            return ['total_sales' => 0, 'order_count' => 0];
        }
        $stats = Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('business_date', $dates)
            ->whereIn('status', OrderStatus::salesStatusValues())
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sales')
            ->first();

        return [
            'total_sales' => (int) $stats->total_sales,
            'order_count' => (int) $stats->order_count,
        ];
    }

    // 欠損日リストを日別に集計（getDailyStatsForRange 用）。キーは Y-m-d 形式
    private function queryDailyStatsByDates(int $tenantId, array $dates): array
    {
        if ($dates === []) {
            return [];
        }
        $rows = Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('business_date', $dates)
            ->whereIn('status', OrderStatus::salesStatusValues())
            ->selectRaw('DATE(business_date) as date_key')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sales')
            ->groupByRaw('DATE(business_date)')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $orderCount = (int) $row->order_count;
            $totalSales = (int) $row->total_sales;
            $result[(string) $row->date_key] = [
                'total_sales' => $totalSales,
                'order_count' => $orderCount,
                'average_order_value' => $orderCount > 0 ? (int) round($totalSales / $orderCount) : 0,
            ];
        }

        return $result;
    }

    // 日次売上キャッシュを範囲指定で逐次取得する
    private function dailySalesCacheCursor(int $tenantId, Carbon $startDate, Carbon $endDate): iterable
    {
        return AnalyticsCache::forTenant($tenantId)
            ->ofType(MetricType::DailySales)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('id')
            ->cursor();
    }

    // リポジトリ生成時点の当日を返す（同一処理内で日付基準を揃える）
    private function todayReference(): Carbon
    {
        return $this->resolvedToday->copy();
    }

    // 時間帯別分布を取得
    public function getHourlyDistribution(int $tenantId, Carbon $date): array
    {
        if ($date->isSameDay($this->todayReference())) {
            return $this->queryRealtimeHourlyDistribution($tenantId, $date);
        }

        // 確定済みの過去日はキャッシュから取得し、集計クエリのコストを回避する
        $cached = $this->analyticsService->getCachedAnalytics(
            $tenantId,
            MetricType::HourlyDistribution,
            $date
        );

        if ($cached && isset($cached['hourly_stats'])) {
            return $cached['hourly_stats'];
        }

        // キャッシュ未生成の場合（バッチ未実行等）はフォールバックとしてDB直接集計する
        return $this->queryRealtimeHourlyDistribution($tenantId, $date);
    }

    // リアルタイム統計をDBから直接取得
    private function queryRealtimeStats(int $tenantId, Carbon $date): array
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
        $average = $orderCount > 0 ? (int) round($totalSales / $orderCount) : 0;

        return [
            'total_sales' => $totalSales,
            'order_count' => $orderCount,
            'average_order_value' => $average,
        ];
    }

    // キャッシュから日次統計を取得
    private function getCachedDayStats(int $tenantId, Carbon $date): array
    {
        $cached = $this->analyticsService->getCachedAnalytics(
            $tenantId,
            MetricType::DailySales,
            $date
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

    // リアルタイムで時間帯別分布を集計
    private function queryRealtimeHourlyDistribution(int $tenantId, Carbon $date): array
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

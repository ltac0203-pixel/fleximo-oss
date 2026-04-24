<?php

declare(strict_types=1);

namespace App\Services\Stats\Queries;

use App\Enums\MetricType;
use App\Enums\OrderStatus;
use App\Models\AnalyticsCache;
use App\Services\Stats\Concerns\FillsMissingDates;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// 任意期間の日付別統計 (Y-m-d キーの連想配列) を取得。グラフ描画で欠損日も
// ゼロ値で必要なため全日付を初期化したうえでキャッシュ・フォールバック・当日リアルタイムで上書きする。
class DailySalesSeriesQuery
{
    use FillsMissingDates;

    public function __construct(private readonly DailySalesQuery $dailySalesQuery) {}

    /**
     * @return array<string, array{total_sales: int, order_count: int, average_order_value: int}>
     */
    public function forRange(int $tenantId, Carbon $startDate, Carbon $endDate, Carbon $today): array
    {
        $result = [];

        // キャッシュに存在しない日付（注文ゼロの日）もグラフ表示に必要なため、全日付を0で初期化する
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $result[$current->format('Y-m-d')] = [
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
            $cursor = AnalyticsCache::forTenant($tenantId)
                ->ofType(MetricType::DailySales)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $cacheEnd->format('Y-m-d')])
                ->orderBy('id')
                ->cursor();

            foreach ($cursor as $cache) {
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
                foreach ($this->aggregateFallbackByDate($tenantId, $missingDates) as $dateKey => $stats) {
                    $result[$dateKey] = $stats;
                }
            }
        }

        // 当日分は注文が随時追加されるため、キャッシュではなくDBから最新値を取得する
        if ($today->between($startDate, $endDate)) {
            $result[$today->format('Y-m-d')] = $this->dailySalesQuery->aggregateRealtime($tenantId, $today);
        }

        return $result;
    }

    /**
     * 呼び出し側で tenant_id を必須にしているため、クエリビルダ直接でも安全。
     *
     * @param  list<string>  $dates
     * @return array<string, array{total_sales: int, order_count: int, average_order_value: int}>
     */
    private function aggregateFallbackByDate(int $tenantId, array $dates): array
    {
        $rows = DB::table('orders')
            ->where('tenant_id', $tenantId)
            ->whereIn('business_date', $dates)
            ->whereIn('status', OrderStatus::salesStatusValues())
            ->select('business_date')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sales')
            ->groupBy('business_date')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $orderCount = (int) ($row->order_count ?? 0);
            $totalSales = (int) ($row->total_sales ?? 0);
            $dateKey = (string) ($row->business_date ?? '');
            $result[$dateKey] = [
                'total_sales' => $totalSales,
                'order_count' => $orderCount,
                'average_order_value' => $this->averageOrderValue($totalSales, $orderCount),
            ];
        }

        return $result;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Stats\Concerns;

use App\Enums\OrderStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait FillsMissingDates
{
    /**
     * 指定期間のうち、与えられたキャッシュ済み日付リストに含まれない Y-m-d 文字列を返す。
     *
     * @param  list<string>  $cachedDates
     * @return list<string>
     */
    protected function findMissingDates(Carbon $start, Carbon $end, array $cachedDates): array
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

    /**
     * 平均客単価を整数に丸めて返す。件数 0 の場合は 0。
     */
    protected function averageOrderValue(int $totalSales, int $orderCount): int
    {
        return $orderCount > 0 ? (int) round($totalSales / $orderCount) : 0;
    }

    /**
     * キャッシュ欠損日を DB から直接集計して合計を返す（合算用）。
     *
     * 呼び出し側は tenant_id を必ず条件に含めるため、Model 経由で TenantScope を
     * 明示的に外す代わりに、クエリビルダを直接使うことで PHPStan の型推論を安定化させる。
     *
     * @param  list<string>  $dates
     * @return array{total_sales: int, order_count: int}
     */
    protected function aggregateSalesByDates(int $tenantId, array $dates): array
    {
        if ($dates === []) {
            return ['total_sales' => 0, 'order_count' => 0];
        }

        $row = DB::table('orders')
            ->where('tenant_id', $tenantId)
            ->whereIn('business_date', $dates)
            ->whereIn('status', OrderStatus::salesStatusValues())
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sales')
            ->first();

        return [
            'total_sales' => (int) ($row->total_sales ?? 0),
            'order_count' => (int) ($row->order_count ?? 0),
        ];
    }
}

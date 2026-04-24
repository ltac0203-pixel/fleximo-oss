<?php

declare(strict_types=1);

namespace App\Services\Stats\Concerns;

use Carbon\Carbon;

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
}

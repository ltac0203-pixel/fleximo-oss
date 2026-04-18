<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\SalesPeriod;
use Carbon\Carbon;

class SalesDataFormatter
{
    public function format(SalesPeriod $period, Carbon $startDate, Carbon $endDate, array $dailyStats): array
    {
        return match ($period) {
            SalesPeriod::Daily => $this->formatDaily($startDate, $endDate, $dailyStats),
            SalesPeriod::Weekly, SalesPeriod::Monthly => $this->formatGrouped($startDate, $endDate, $period, $dailyStats),
        };
    }

    private function formatDaily(Carbon $startDate, Carbon $endDate, array $dailyStats): array
    {
        $result = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $dateKey = $current->format('Y-m-d');
            $stats = $dailyStats[$dateKey] ?? ['total_sales' => 0, 'order_count' => 0];
            $result[] = [
                'date' => $dateKey,
                'total_sales' => $stats['total_sales'],
                'order_count' => $stats['order_count'],
            ];
            $current->addDay();
        }

        return $result;
    }

    private function formatGrouped(Carbon $groupStartDate, Carbon $endDate, SalesPeriod $period, array $dailyStats): array
    {
        $result = [];
        $current = $groupStartDate->copy();

        while ($current->lte($endDate)) {
            $bucketEnd = $this->resolveBucketEnd($current, $endDate, $period);
            $stats = $this->sumDailyStats($dailyStats, $current, $bucketEnd);

            $result[] = [
                'date' => $period === SalesPeriod::Weekly ? $current->format('Y-m-d') : $current->format('Y-m'),
                'total_sales' => $stats['total_sales'],
                'order_count' => $stats['order_count'],
            ];

            if ($period === SalesPeriod::Weekly) {
                $current->addWeek();
            } else {
                $current->addMonth();
            }
        }

        return $result;
    }

    private function resolveBucketEnd(Carbon $bucketStart, Carbon $rangeEnd, SalesPeriod $period): Carbon
    {
        $bucketEnd = $period === SalesPeriod::Weekly
            ? $bucketStart->copy()->endOfWeek(Carbon::SUNDAY)
            : $bucketStart->copy()->endOfMonth();

        return $bucketEnd->gt($rangeEnd) ? $rangeEnd->copy() : $bucketEnd;
    }

    private function sumDailyStats(array $dailyStats, Carbon $startDate, Carbon $endDate): array
    {
        $totalSales = 0;
        $orderCount = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $stats = $dailyStats[$current->format('Y-m-d')] ?? ['total_sales' => 0, 'order_count' => 0];
            $totalSales += (int) ($stats['total_sales'] ?? 0);
            $orderCount += (int) ($stats['order_count'] ?? 0);
            $current->addDay();
        }

        return [
            'total_sales' => $totalSales,
            'order_count' => $orderCount,
        ];
    }
}

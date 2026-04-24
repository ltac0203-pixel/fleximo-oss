<?php

declare(strict_types=1);

namespace App\Services\Stats\Queries;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Scopes\TenantScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// テナント別の顧客分析。期間内の注文から、ユニーク顧客数・新規顧客数・リピート顧客数・
// リピート率を算出する。新規判定は「期間前に当テナント向けの売上対象注文が無い顧客」。
class CustomerInsightsQuery
{
    /**
     * @return array{unique_customers: int, new_customers: int, repeat_customers: int, repeat_rate: float|int}
     */
    public function forRange(int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        $dateRange = [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')];
        $salesStatuses = OrderStatus::salesStatusValues();

        // リピート率算出の母数として、期間内に1回以上注文したユニーク顧客数を取得する
        $uniqueCustomers = (int) Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereBetween('business_date', $dateRange)
            ->whereIn('status', $salesStatuses)
            ->distinct('user_id')
            ->count('user_id');

        // 期間内の顧客のうち、期間前に注文が無い人＝新規顧客と判定する
        $newCustomers = (int) Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereBetween('business_date', $dateRange)
            ->whereIn('status', $salesStatuses)
            ->whereNotExists(function ($query) use ($tenantId, $startDate, $salesStatuses) {
                $query->select(DB::raw(1))
                    ->from('orders as prior')
                    ->whereColumn('prior.user_id', 'orders.user_id')
                    ->where('prior.tenant_id', $tenantId)
                    ->where('prior.business_date', '<', $startDate->format('Y-m-d'))
                    ->whereIn('prior.status', $salesStatuses);
            })
            ->distinct()
            ->count('user_id');

        $repeatCustomers = $uniqueCustomers - $newCustomers;
        $repeatRate = $uniqueCustomers > 0 ? round(($repeatCustomers / $uniqueCustomers) * 100, 1) : 0;

        return [
            'unique_customers' => $uniqueCustomers,
            'new_customers' => $newCustomers,
            'repeat_customers' => $repeatCustomers,
            'repeat_rate' => $repeatRate,
        ];
    }
}

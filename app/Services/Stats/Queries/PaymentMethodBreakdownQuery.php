<?php

declare(strict_types=1);

namespace App\Services\Stats\Queries;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use Carbon\Carbon;

// 決済方法別の件数・金額を集計する。Completed ステータスのみを対象とし、
// orders テーブルと JOIN して business_date で絞り込む。
class PaymentMethodBreakdownQuery
{
    /**
     * @return array<string, array{count: int, amount: int}>
     */
    public function forRange(int $tenantId, Carbon $startDate, Carbon $endDate): array
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
}

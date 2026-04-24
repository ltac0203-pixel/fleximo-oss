<?php

declare(strict_types=1);

namespace App\Services\Stats\Queries;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use Carbon\Carbon;

// 指定テナントの人気メニュー (数量降順) を startDate 以降で集計する。
// 売上対象ステータス (OrderStatus::salesStatusValues) のみが対象。
class TopMenuItemsQuery
{
    /**
     * @return list<object>
     */
    public function forStartDate(int $tenantId, Carbon $startDate, int $limit): array
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
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class OrderService
{
    // ユーザーの注文一覧を取得する（全テナント横断）
    // Order::forCustomerAcrossTenants() で TenantScope を除外しています。顧客は複数テナントで注文するため、
    // 全テナントの注文履歴を一覧表示する必要があります。
    // user_id の WHERE 条件でセキュリティスコープを担保しています。
    public function getUserOrders(User $user, ?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Order::forCustomerAcrossTenants($user->id)
            ->withCustomerList()
            ->orderBy('created_at', 'desc');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    // ユーザーの売上対象注文一覧を取得する（全テナント横断）
    public function getUserSalesOrders(User $user, ?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Order::forCustomerAcrossTenants($user->id)
            ->withCustomerList()
            ->whereIn('status', OrderStatus::salesStatusValues())
            ->orderBy('created_at', 'desc');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    // ユーザーの決済完了注文一覧を取得する（全テナント横断）
    public function getUserPaidOrders(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return Order::forCustomerAcrossTenants($user->id)
            ->withCustomerList()
            ->where('status', OrderStatus::Paid->value)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getOrderWithDetails(Order $order): Order
    {
        return $order->loadCustomerDetail();
    }

    public function getRecentOrders(User $user, int $limit = 5): Collection
    {
        return Order::forCustomerAcrossTenants($user->id)
            ->with(['tenant', 'tenant.businessHours'])
            ->latest()
            ->take($limit)
            ->get();
    }

    public function userOwnsOrder(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }
}

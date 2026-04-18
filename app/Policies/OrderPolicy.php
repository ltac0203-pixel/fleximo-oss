<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    // 顧客向け認可メソッド
    // ユーザーが注文一覧を表示できるか
    public function viewAny(User $user): bool
    {
        return $user->isCustomer();
    }

    // ユーザーが注文詳細を表示できるか
    public function view(User $user, Order $order): bool
    {
        return $user->isCustomer() && $user->id === $order->user_id;
    }

    // ユーザーが注文を作成できるか
    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    // ユーザーが注文を更新できるか（顧客は不可）
    public function update(User $user, Order $order): bool
    {
        return false;
    }

    // ユーザーが注文を削除できるか（顧客は不可）
    public function delete(User $user, Order $order): bool
    {
        return false;
    }

    // 顧客が注文をキャンセルできるか
    public function cancel(User $user, Order $order): bool
    {
        if (! $user->isCustomer()) {
            return false;
        }

        if ($user->id !== $order->user_id) {
            return false;
        }

        return $order->canBeCancelled();
    }

    // 顧客が注文を再注文できるか
    public function reorder(User $user, Order $order): bool
    {
        if (! $user->isCustomer()) {
            return false;
        }

        if ($user->id !== $order->user_id) {
            return false;
        }

        return $order->isCompleted();
    }

    // テナント向け認可メソッド

    // テナントスタッフが注文一覧を表示できるか
    public function viewAnyForTenant(User $user): bool
    {
        return $user->isTenantAdmin() || $user->isTenantStaff();
    }

    // テナントスタッフが注文詳細を表示できるか
    public function viewForTenant(User $user, Order $order): bool
    {
        if (! $user->isTenantAdmin() && ! $user->isTenantStaff()) {
            return false;
        }

        return $user->getTenantId() === $order->tenant_id;
    }

    // テナントスタッフが注文ステータスを更新できるか
    public function updateStatus(User $user, Order $order): bool
    {
        if (! $user->isTenantAdmin() && ! $user->isTenantStaff()) {
            return false;
        }

        return $user->getTenantId() === $order->tenant_id;
    }

    // テナントスタッフが注文をキャンセルできるか
    public function cancelForTenant(User $user, Order $order): bool
    {
        if (! $user->isTenantAdmin() && ! $user->isTenantStaff()) {
            return false;
        }

        if ($user->getTenantId() !== $order->tenant_id) {
            return false;
        }

        return $order->canBeCancelled();
    }

    // 管理者向け認可メソッド

    // 注文を返金できるか（Webhook自動処理のため手動実行は不可）
    public function refund(User $user, Order $order): bool
    {
        return false;
    }

    // 注文を復元できるか（SoftDeletes未使用のため不可）
    public function restore(User $user, Order $order): bool
    {
        return false;
    }

    // 注文を完全削除できるか（監査証跡保持のため不可）
    public function forceDelete(User $user, Order $order): bool
    {
        return false;
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;

class MenuItemPolicy
{
    // 商品一覧を表示できるか
    public function viewAny(User $user): bool
    {
        return $user->isTenantAdmin() || $user->isTenantStaff();
    }

    // 商品の詳細を表示できるか
    public function view(User $user, MenuItem $item): bool
    {
        if (! $user->isTenantAdmin() && ! $user->isTenantStaff()) {
            return false;
        }

        return $user->getTenantId() === $item->tenant_id;
    }

    // 商品を作成できるか
    public function create(User $user): bool
    {
        return $user->isTenantAdmin();
    }

    // 商品を更新できるか
    public function update(User $user, MenuItem $item): bool
    {
        if (! $user->isTenantAdmin()) {
            return false;
        }

        return $user->getTenantId() === $item->tenant_id;
    }

    // 商品を削除できるか
    public function delete(User $user, MenuItem $item): bool
    {
        return $this->update($user, $item);
    }

    // 売り切れ切替ができるか（スタッフも可）
    public function toggleSoldOut(User $user, MenuItem $item): bool
    {
        if (! $user->isTenantAdmin() && ! $user->isTenantStaff()) {
            return false;
        }

        return $user->getTenantId() === $item->tenant_id;
    }

    // オプショングループの紐付けができるか
    public function manageOptionGroups(User $user, MenuItem $item): bool
    {
        return $this->update($user, $item);
    }
}

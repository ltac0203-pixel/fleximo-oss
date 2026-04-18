<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MenuCategory;
use App\Models\User;

class MenuCategoryPolicy
{
    // カテゴリ一覧を表示できるか
    public function viewAny(User $user): bool
    {
        return $user->isTenantAdmin() || $user->isTenantStaff();
    }

    // カテゴリの詳細を表示できるか
    public function view(User $user, MenuCategory $category): bool
    {
        if (! $user->isTenantAdmin() && ! $user->isTenantStaff()) {
            return false;
        }

        return $user->getTenantId() === $category->tenant_id;
    }

    // カテゴリを作成できるか
    public function create(User $user): bool
    {
        return $user->isTenantAdmin();
    }

    // カテゴリを更新できるか
    public function update(User $user, MenuCategory $category): bool
    {
        if (! $user->isTenantAdmin()) {
            return false;
        }

        return $user->getTenantId() === $category->tenant_id;
    }

    // カテゴリを削除できるか
    public function delete(User $user, MenuCategory $category): bool
    {
        return $this->update($user, $category);
    }

    // カテゴリの並び順を変更できるか
    public function reorder(User $user): bool
    {
        return $user->isTenantAdmin();
    }
}

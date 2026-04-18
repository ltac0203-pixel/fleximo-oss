<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OptionGroup;
use App\Models\User;

class OptionGroupPolicy
{
    // オプショングループ一覧を表示できるか
    public function viewAny(User $user): bool
    {
        return $user->isTenantAdmin() || $user->isTenantStaff();
    }

    // オプショングループの詳細を表示できるか
    public function view(User $user, OptionGroup $optionGroup): bool
    {
        if (! $user->isTenantAdmin() && ! $user->isTenantStaff()) {
            return false;
        }

        return $user->getTenantId() === $optionGroup->tenant_id;
    }

    // オプショングループを作成できるか
    public function create(User $user): bool
    {
        return $user->isTenantAdmin();
    }

    // オプショングループを更新できるか
    public function update(User $user, OptionGroup $optionGroup): bool
    {
        if (! $user->isTenantAdmin()) {
            return false;
        }

        return $user->getTenantId() === $optionGroup->tenant_id;
    }

    // オプショングループを削除できるか
    public function delete(User $user, OptionGroup $optionGroup): bool
    {
        return $this->update($user, $optionGroup);
    }
}

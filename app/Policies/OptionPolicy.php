<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\User;

class OptionPolicy
{
    // オプション一覧を表示できるか
    public function viewAny(User $user, OptionGroup $optionGroup): bool
    {
        if (! $user->isTenantAdmin() && ! $user->isTenantStaff()) {
            return false;
        }

        return $user->getTenantId() === $optionGroup->tenant_id;
    }

    // オプションを作成できるか
    public function create(User $user, OptionGroup $optionGroup): bool
    {
        if (! $user->isTenantAdmin()) {
            return false;
        }

        return $user->getTenantId() === $optionGroup->tenant_id;
    }

    // オプションを更新できるか
    public function update(User $user, Option $option): bool
    {
        if (! $user->isTenantAdmin()) {
            return false;
        }

        return $user->getTenantId() === $option->optionGroup->tenant_id;
    }

    // オプションを削除できるか
    public function delete(User $user, Option $option): bool
    {
        return $this->update($user, $option);
    }
}

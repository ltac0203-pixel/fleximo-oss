<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class StaffPolicy
{
    // スタッフ一覧を表示できるか
    public function viewAny(User $user): bool
    {
        return $user->isTenantAdmin() || $user->isTenantStaff();
    }

    // 特定のスタッフの詳細を表示できるか
    public function view(User $user, User $staff): bool
    {
        // 顧客や他テナントのユーザーがスタッフ情報を閲覧できないようにする
        if (! $user->isTenantAdmin() && ! $user->isTenantStaff()) {
            return false;
        }

        // テナント間のデータ漏洩を防ぐため、同一テナント所属であることを保証する
        if ($user->getTenantId() !== $staff->getTenantId()) {
            return false;
        }

        // 管理者ロールのユーザーが誤って管理対象にならないよう、スタッフロールに限定する
        if (! $staff->isTenantStaff()) {
            return false;
        }

        return true;
    }

    // スタッフを作成できるか
    public function create(User $user): bool
    {
        return $user->isTenantAdmin();
    }

    // スタッフ情報を更新できるか
    public function update(User $user, User $staff): bool
    {
        if (! $user->isTenantAdmin()) {
            return false;
        }

        // テナント間のデータ漏洩を防ぐため、同一テナント所属であることを保証する
        if ($user->getTenantId() !== $staff->getTenantId()) {
            return false;
        }

        // 管理者ロールのユーザーが誤って編集対象にならないよう、スタッフロールに限定する
        if (! $staff->isTenantStaff()) {
            return false;
        }

        return true;
    }

    // スタッフを削除できるか
    public function delete(User $user, User $staff): bool
    {
        if (! $user->isTenantAdmin()) {
            return false;
        }

        if ($user->getTenantId() !== $staff->getTenantId()) {
            return false;
        }

        if (! $staff->isTenantStaff()) {
            return false;
        }

        return true;
    }
}

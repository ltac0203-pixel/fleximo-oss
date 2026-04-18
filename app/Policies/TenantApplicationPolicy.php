<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantApplication;
use App\Models\User;

class TenantApplicationPolicy
{
    // 申し込み一覧を表示できるか
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    // 申し込み詳細を表示できるか
    public function view(User $user, TenantApplication $application): bool
    {
        return $user->isAdmin();
    }

    // 審査を開始できるか
    public function startReview(User $user, TenantApplication $application): bool
    {
        return $user->isAdmin();
    }

    // 申し込みを承認できるか
    public function approve(User $user, TenantApplication $application): bool
    {
        return $user->isAdmin();
    }

    // 申し込みを却下できるか
    public function reject(User $user, TenantApplication $application): bool
    {
        return $user->isAdmin();
    }

    // 内部メモを更新できるか
    public function updateNotes(User $user, TenantApplication $application): bool
    {
        return $user->isAdmin();
    }
}

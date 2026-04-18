<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;

class FavoriteService
{
    public function getFavoriteTenantIds(User $user): Collection
    {
        return $user->favoriteTenants()->pluck('tenants.id');
    }

    // お気に入りをトグルする（追加/解除）
    // 戻り値は新状態（true=お気に入り登録済み）
    public function toggleFavorite(User $user, Tenant $tenant): bool
    {
        $result = $user->favoriteTenants()->toggle($tenant->id);

        return ! empty($result['attached']);
    }
}

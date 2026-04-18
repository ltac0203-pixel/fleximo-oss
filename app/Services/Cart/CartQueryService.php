<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CartQueryService
{
    public function findUserCartForTenant(User $user, int $tenantId): ?Cart
    {
        return Cart::withoutGlobalScope(TenantScope::class)
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function getCartWithRelationsOrFail(int $cartId): Cart
    {
        return Cart::withoutGlobalScope(TenantScope::class)
            ->withFullRelations()
            ->findOrFail($cartId);
    }

    // ユーザーの全カートを取得（テナント別）
    // Cart モデルは BelongsToTenant を使用しており TenantScope が自動適用されるため、
    // 顧客向けクエリでは withoutGlobalScope で明示的にバイパスする。
    // @see \App\Models\Cart 設計意図の詳細
    public function getUserCarts(User $user): Collection
    {
        return DB::transaction(function () use ($user) {
            // 日付が変わった古いカートを自動削除（cascadeOnDeleteで子レコードも自動削除）
            Cart::withoutGlobalScope(TenantScope::class)
                ->where('user_id', $user->id)
                ->where('updated_at', '<', Carbon::today())
                ->delete();

            return Cart::withoutGlobalScope(TenantScope::class)
                ->withFullRelations()
                ->where('user_id', $user->id)
                ->get();
        });
    }

    // 指定テナントのカートを取得、または作成
    public function getOrCreateCart(User $user, int $tenantId): Cart
    {
        return Cart::withoutGlobalScope(TenantScope::class)
            ->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'tenant_id' => $tenantId,
                ]
            );
    }

    public function getCheckoutCart(User $user): ?Cart
    {
        return Cart::withoutGlobalScope(TenantScope::class)
            ->withFullRelations()
            ->where('user_id', $user->id)
            ->whereHas('items')
            ->first();
    }
}

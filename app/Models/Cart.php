<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

/**
 * BelongsToTenant でテナント管理者/スタッフ側のクエリを自動スコープする。
 * 顧客は複数テナントのカートを保持するため、顧客向けクエリでは
 * withoutGlobalScope(TenantScope::class) で明示的にバイパスすること。
 *
 * @see BelongsToTenant
 * @see Order::scopeForCustomerAcrossTenants()
 *
 * @property-read Tenant $tenant
 */
class Cart extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // tenant() は BelongsToTenant トレイトが提供

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // N+1問題を防ぐため、total/item_countアクセサの前に必ず適用すること
    public function scopeWithFullRelations($query)
    {
        return $query->with([
            'tenant.businessHours',
            'items' => function ($query) {
                $query->orderBy('created_at', 'asc');
            },
            'items.menuItem',
            'items.options.option',
        ]);
    }

    public function getTotalAttribute(): int
    {
        if (! $this->relationLoaded('items')) {
            Log::warning('Cart::$total accessed without eager-loaded items; auto-loading via loadMissing.', [
                'cart_id' => $this->id,
            ]);
            $this->loadMissing(['items.menuItem', 'items.options.option']);
        }

        return $this->items->sum('subtotal');
    }

    public function getItemCountAttribute(): int
    {
        if (! $this->relationLoaded('items')) {
            Log::warning('Cart::$item_count accessed without eager-loaded items; auto-loading via loadMissing.', [
                'cart_id' => $this->id,
            ]);
            $this->loadMissing(['items.menuItem', 'items.options.option']);
        }

        return $this->items->sum('quantity');
    }

    public function isEmpty(): bool
    {
        if (! $this->relationLoaded('items')) {
            Log::warning('Cart::isEmpty() accessed without eager-loaded items; auto-loading via loadMissing.', [
                'cart_id' => $this->id,
            ]);
            $this->loadMissing('items');
        }

        return $this->items->isEmpty();
    }

    // 顧客ルートでは TenantContext が未設定のため、ルートモデルバインディングで TenantScope を除外
    // 認証ユーザーのカートのみに限定し、カートID列挙を防止する
    public function resolveRouteBinding($value, $field = null): ?self
    {
        $query = static::withoutGlobalScope(TenantScope::class)
            ->where($field ?? $this->getRouteKeyName(), $value);

        $user = request()->user();
        if ($user) {
            $query->where('user_id', $user->id);
        }

        return $query->first();
    }

    public function scopeForCustomerAcrossTenants(Builder $query, int $userId): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where('user_id', $userId);
    }
}

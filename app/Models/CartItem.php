<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Note: BelongsToTenant を使用しない。Cart が BelongsToTenant を持つため、
// cart() リレーションでは withoutGlobalScope で TenantScope をバイパスする。
// セキュリティは CartPolicy / CartItemPolicy（user_id による所有権チェック）で担保する。
class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'tenant_id',
        'menu_item_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    // このカート商品が属するカート
    // Cart は BelongsToTenant を使用するため、顧客コンテキスト（TenantContext=null）で
    // NPE を防ぐために TenantScope をバイパスする
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class)
            ->withoutGlobalScope(TenantScope::class);
    }

    // このカート商品のメニュー商品
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class)
            ->withoutGlobalScope(TenantScope::class);
    }

    // このカート商品のオプション
    public function options(): HasMany
    {
        return $this->hasMany(CartItemOption::class);
    }

    // カートアイテムの小計を計算（商品価格 + オプション価格）× 数量
    public function getSubtotalAttribute(): int
    {
        if (! $this->relationLoaded('menuItem')) {
            throw new \LogicException(
                'CartItem::$subtotal アクセサは menuItem リレーションがロードされている必要があります。'
                    .' Cart::withFullRelations() を使用してください。'
            );
        }
        if (! $this->relationLoaded('options')) {
            throw new \LogicException(
                'CartItem::$subtotal アクセサは options リレーションがロードされている必要があります。'
                    .' Cart::withFullRelations() を使用してください。'
            );
        }

        $menuItemPrice = $this->menuItem?->price ?? 0;
        $optionsPrice = $this->options->sum(fn (CartItemOption $option) => $option->option?->price ?? 0);

        return ($menuItemPrice + $optionsPrice) * $this->quantity;
    }
}

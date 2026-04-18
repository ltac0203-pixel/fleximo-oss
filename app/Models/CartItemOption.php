<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Note: BelongsToTenant を使用しない。Cart が BelongsToTenant を持つが、
// CartItemOption は Cart に直接リレーションしないため影響を受けない。
// セキュリティは CartPolicy / CartItemPolicy で担保する。
class CartItemOption extends Model
{
    use HasFactory;

    // CartItemOptionはoption参照なしでは意味をなさないため常時ロード
    protected $with = ['option'];

    // タイムスタンプの更新を無効化（updated_atがない）
    public const UPDATED_AT = null;

    protected $fillable = [
        'cart_item_id',
        'tenant_id',
        'option_id',
    ];

    // このオプション選択が属するカート商品
    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(CartItem::class);
    }

    // このオプション選択のオプション
    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class)
            ->withoutGlobalScope(TenantScope::class);
    }
}

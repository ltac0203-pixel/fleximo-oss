<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use BelongsToTenant, HasFactory;

    // updated_at は使用しない
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'tenant_id',
        'menu_item_id',
        'name',
        'price',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quantity' => 'integer',
        ];
    }

    // この商品が属する注文
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // 元のメニュー商品（削除されている場合は null）
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(OrderItemOption::class);
    }

    // 小計を計算するアクセサ
    // (商品価格 + オプション合計) × 数量
    protected function subtotal(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $optionsTotal = $this->options->sum('price');

                return ($this->price + $optionsTotal) * $this->quantity;
            }
        );
    }
}

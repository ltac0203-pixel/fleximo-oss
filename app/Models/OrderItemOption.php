<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemOption extends Model
{
    use BelongsToTenant, HasFactory;

    // updated_at は使用しない
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_item_id',
        'tenant_id',
        'option_id',
        'name',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
        ];
    }

    // このオプションが属する注文商品
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    // 元のオプション（削除されている場合は null）
    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MenuItemOptionGroup extends Pivot
{
    // テーブル名
    protected $table = 'menu_item_option_groups';

    // updated_atは使用しない
    public const UPDATED_AT = null;

    protected $fillable = [
        'menu_item_id',
        'option_group_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    // この中間レコードが属する商品
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    // この中間レコードが属するオプショングループ
    public function optionGroup(): BelongsTo
    {
        return $this->belongsTo(OptionGroup::class);
    }
}

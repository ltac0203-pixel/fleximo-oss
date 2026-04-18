<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MenuItemCategory extends Pivot
{
    // テーブル名
    protected $table = 'menu_item_categories';

    protected $fillable = [
        'menu_item_id',
        'category_id',
    ];

    // updated_at は使用しない
    public const UPDATED_AT = null;
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum ReorderSkipReason: string
{
    case MenuItemDeleted = 'menu_item_deleted';
    case Inactive = 'inactive';
    case SoldOut = 'sold_out';
    case OutsideTimeWindow = 'outside_time_window';
    case OptionConstraintsChanged = 'option_constraints_changed';

    public function label(): string
    {
        return match ($this) {
            self::MenuItemDeleted => '商品が削除されました',
            self::Inactive => '現在販売停止中です',
            self::SoldOut => '売り切れです',
            self::OutsideTimeWindow => '販売時間外です',
            self::OptionConstraintsChanged => 'オプション構成が変更されました',
        };
    }
}

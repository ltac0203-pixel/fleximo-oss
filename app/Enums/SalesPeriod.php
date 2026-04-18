<?php

declare(strict_types=1);

namespace App\Enums;

enum SalesPeriod: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Daily => '日次',
            self::Weekly => '週次',
            self::Monthly => '月次',
        };
    }
}

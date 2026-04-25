<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\Carbon;

enum TopItemsPeriod: string
{
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';

    public function startDate(Carbon $today): Carbon
    {
        return match ($this) {
            self::Week => $today->copy()->subDays(7),
            self::Month => $today->copy()->subDays(30),
            self::Year => $today->copy()->subDays(365),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Week => '直近7日',
            self::Month => '直近30日',
            self::Year => '直近1年',
        };
    }
}

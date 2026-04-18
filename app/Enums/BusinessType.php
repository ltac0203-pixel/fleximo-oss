<?php

declare(strict_types=1);

namespace App\Enums;

enum BusinessType: string
{
    case Restaurant = 'restaurant';
    case Cafe = 'cafe';
    case Izakaya = 'izakaya';
    case Bakery = 'bakery';
    case FastFood = 'fast_food';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Restaurant => '飲食店',
            self::Cafe => 'カフェ',
            self::Izakaya => '居酒屋',
            self::Bakery => 'ベーカリー',
            self::FastFood => 'ファストフード',
            self::Other => 'その他',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }
}

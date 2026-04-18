<?php

declare(strict_types=1);

namespace App\Enums;

// 特定原材料8品目（ビットマスク管理）
enum Allergen: int
{
    case Shrimp = 1;       // えび
    case Crab = 2;         // かに
    case Walnut = 4;       // くるみ
    case Wheat = 8;        // 小麦
    case Buckwheat = 16;   // そば
    case Egg = 32;         // 卵
    case Milk = 64;        // 乳
    case Peanut = 128;     // 落花生

    // 日本語ラベルを返す
    public function label(): string
    {
        return match ($this) {
            self::Shrimp => 'えび',
            self::Crab => 'かに',
            self::Walnut => 'くるみ',
            self::Wheat => '小麦',
            self::Buckwheat => 'そば',
            self::Egg => '卵',
            self::Milk => '乳',
            self::Peanut => '落花生',
        };
    }

    // ビットマスクから該当するcase配列を返す
    public static function fromBitmask(int $bitmask): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            if (($bitmask & $case->value) !== 0) {
                $result[] = $case;
            }
        }

        return $result;
    }

    // ビットマスクから日本語ラベル配列を返す
    public static function labels(int $bitmask): array
    {
        return array_map(
            fn (self $case) => $case->label(),
            self::fromBitmask($bitmask)
        );
    }
}

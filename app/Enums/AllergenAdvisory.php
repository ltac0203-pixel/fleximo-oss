<?php

declare(strict_types=1);

namespace App\Enums;

// 推奨表示20品目（ビットマスク管理）
enum AllergenAdvisory: int
{
    case Almond = 1;          // アーモンド
    case Abalone = 2;         // あわび
    case Squid = 4;           // いか
    case Salmon_roe = 8;      // いくら
    case Orange = 16;         // オレンジ
    case Cashew = 32;         // カシューナッツ
    case Kiwi = 64;           // キウイフルーツ
    case Beef = 128;          // 牛肉
    case Sesame = 256;        // ごま
    case Salmon = 512;        // さけ
    case Mackerel = 1024;     // さば
    case Soybean = 2048;      // 大豆
    case Chicken = 4096;      // 鶏肉
    case Banana = 8192;       // バナナ
    case Pork = 16384;        // 豚肉
    case Matsutake = 32768;   // まつたけ
    case Peach = 65536;       // もも
    case Yam = 131072;        // やまいも
    case Apple = 262144;      // りんご
    case Gelatin = 524288;    // ゼラチン

    // 日本語ラベルを返す
    public function label(): string
    {
        return match ($this) {
            self::Almond => 'アーモンド',
            self::Abalone => 'あわび',
            self::Squid => 'いか',
            self::Salmon_roe => 'いくら',
            self::Orange => 'オレンジ',
            self::Cashew => 'カシューナッツ',
            self::Kiwi => 'キウイフルーツ',
            self::Beef => '牛肉',
            self::Sesame => 'ごま',
            self::Salmon => 'さけ',
            self::Mackerel => 'さば',
            self::Soybean => '大豆',
            self::Chicken => '鶏肉',
            self::Banana => 'バナナ',
            self::Pork => '豚肉',
            self::Matsutake => 'まつたけ',
            self::Peach => 'もも',
            self::Yam => 'やまいも',
            self::Apple => 'りんご',
            self::Gelatin => 'ゼラチン',
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

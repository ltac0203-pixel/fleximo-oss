<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Banned = 'banned';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'アクティブ',
            self::Suspended => '一時停止',
            self::Banned => 'BAN',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Suspended => 'yellow',
            self::Banned => 'red',
        };
    }

    public function isRestricted(): bool
    {
        return in_array($this, [self::Suspended, self::Banned]);
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Active => in_array($status, [self::Suspended, self::Banned]),
            self::Suspended => in_array($status, [self::Active, self::Banned]),
            // BANは最終状態であり、復帰は管理者による特別操作を必要とする
            self::Banned => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

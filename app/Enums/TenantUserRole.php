<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantUserRole: string
{
    case Admin = 'admin';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Admin => '管理者',
            self::Staff => 'スタッフ',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case TenantAdmin = 'tenant_admin';
    case TenantStaff = 'tenant_staff';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => '管理者',
            self::TenantAdmin => 'テナント管理者',
            self::TenantStaff => 'テナントスタッフ',
            self::Customer => '顧客',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isTenantRole(): bool
    {
        return in_array($this, [self::TenantAdmin, self::TenantStaff]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

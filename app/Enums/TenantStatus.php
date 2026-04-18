<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '審査中',
            self::Active => '有効',
            self::Inactive => '無効',
            self::Suspended => '停止',
            self::Rejected => '却下',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Pending => in_array($status, [self::Active, self::Rejected]),
            self::Active => in_array($status, [self::Inactive, self::Suspended]),
            self::Inactive => $status === self::Active,
            self::Suspended => in_array($status, [self::Active, self::Inactive]),
            // 却下は最終状態であり、再申請は新規申し込みとして処理する
            self::Rejected => false,
        };
    }
}

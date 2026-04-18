<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantApplicationStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '審査待ち',
            self::UnderReview => '審査中',
            self::Approved => '承認済み',
            self::Rejected => '却下',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::UnderReview => 'blue',
            self::Approved => 'green',
            self::Rejected => 'red',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Pending => in_array($status, [self::UnderReview, self::Rejected]),
            self::UnderReview => in_array($status, [self::Approved, self::Rejected]),
            // 承認・却下は最終状態であり、状態巻き戻しによるデータ不整合を防ぐ
            self::Approved, self::Rejected => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return __('enums.payment_status.'.$this->value);
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Pending => in_array($status, [self::Processing, self::Failed]),
            self::Processing => in_array($status, [self::Completed, self::Failed]),
            // 完了・失敗は決済の最終結果であり、状態巻き戻しによる二重決済や不整合を防ぐ
            self::Completed, self::Failed => false,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Completed,
            self::Failed,
        ]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

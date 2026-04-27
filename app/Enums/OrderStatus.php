<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case Ready = 'ready';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case PaymentFailed = 'payment_failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return __('enums.order_status.'.$this->value);
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::PendingPayment => in_array($status, [self::Paid, self::PaymentFailed]),
            self::Paid => in_array($status, [self::Accepted, self::Cancelled]),
            self::Accepted => in_array($status, [self::InProgress, self::Cancelled]),
            self::InProgress => in_array($status, [self::Ready, self::Cancelled]),
            self::Ready => in_array($status, [self::Completed, self::Cancelled]),
            self::Cancelled => $status === self::Refunded,
            // 完了・失敗・返金済みは業務上の最終状態であり、状態巻き戻しによるデータ不整合を防ぐ
            self::Completed, self::PaymentFailed, self::Refunded => false,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Completed,
            self::PaymentFailed,
            self::Refunded,
        ]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::Paid,
            self::Accepted,
            self::InProgress,
            self::Ready,
        ]);
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::Accepted,
            self::InProgress,
            self::Ready,
        ]);
    }

    public function isKdsVisible(): bool
    {
        return in_array($this, [
            self::Paid,
            self::Accepted,
            self::InProgress,
            self::Ready,
        ]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function salesStatuses(): array
    {
        return [
            self::Paid,
            self::Accepted,
            self::InProgress,
            self::Ready,
            self::Completed,
        ];
    }

    public static function salesStatusValues(): array
    {
        return array_map(fn (self $s) => $s->value, self::salesStatuses());
    }
}

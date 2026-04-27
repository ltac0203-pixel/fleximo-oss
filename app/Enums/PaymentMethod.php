<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Card = 'card';
    case PayPay = 'paypay';

    public function label(): string
    {
        return __('enums.payment_method.'.$this->value);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function toFincodePayType(): string
    {
        return match ($this) {
            self::Card => 'Card',
            self::PayPay => 'Paypay',
        };
    }
}

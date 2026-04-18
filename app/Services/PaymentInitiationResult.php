<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;

class PaymentInitiationResult
{
    public function __construct(
        public readonly Payment $payment,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $fincodeId = null,
        public readonly ?string $accessId = null,
        public readonly bool $requiresRedirect = false,
        public readonly bool $requiresToken = false,
    ) {}

    // PayPay決済用の結果を生成する
    public static function forPayPay(Payment $payment, string $redirectUrl, string $fincodeId, ?string $accessId = null): self
    {
        return new self(
            payment: $payment,
            redirectUrl: $redirectUrl,
            fincodeId: $fincodeId,
            accessId: $accessId,
            requiresRedirect: true,
            requiresToken: false,
        );
    }

    // クレジットカード決済用の結果を生成する
    public static function forCard(Payment $payment, string $fincodeId, string $accessId): self
    {
        return new self(
            payment: $payment,
            redirectUrl: null,
            fincodeId: $fincodeId,
            accessId: $accessId,
            requiresRedirect: false,
            requiresToken: true,
        );
    }

    // 配列に変換する（APIレスポンス用）
    public static function forSavedCard(Payment $payment, string $fincodeId, string $accessId): self
    {
        return new self(
            payment: $payment,
            redirectUrl: null,
            fincodeId: $fincodeId,
            accessId: $accessId,
            requiresRedirect: false,
            requiresToken: false,
        );
    }

    public function toArray(): array
    {
        $result = [
            'payment_id' => $this->payment->id,
            'requires_redirect' => $this->requiresRedirect,
            'requires_token' => $this->requiresToken,
        ];

        if ($this->requiresRedirect && $this->redirectUrl) {
            $result['redirect_url'] = $this->redirectUrl;
        }

        if ($this->requiresToken) {
            $result['fincode_id'] = $this->fincodeId;
            $result['access_id'] = $this->accessId;
        }

        return $result;
    }
}

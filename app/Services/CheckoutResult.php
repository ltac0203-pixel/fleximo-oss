<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;

class CheckoutResult
{
    public function __construct(
        public readonly Order $order,
        public readonly Payment $payment,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $fincodeId = null,
        public readonly ?string $accessId = null,
        public readonly bool $requiresRedirect = false,
        public readonly bool $requiresToken = false,
        public readonly bool $cartClearFailed = false,
    ) {}

    // PaymentInitiationResultからCheckoutResultを生成する
    public static function fromPaymentInitiation(
        Order $order,
        PaymentInitiationResult $paymentResult,
        bool $cartClearFailed = false
    ): self {
        return new self(
            order: $order,
            payment: $paymentResult->payment,
            redirectUrl: $paymentResult->redirectUrl,
            fincodeId: $paymentResult->fincodeId,
            accessId: $paymentResult->accessId,
            requiresRedirect: $paymentResult->requiresRedirect,
            requiresToken: $paymentResult->requiresToken,
            cartClearFailed: $cartClearFailed,
        );
    }

    // 配列に変換する（APIレスポンス用）
    public function toArray(): array
    {
        $result = [
            'order' => [
                'id' => $this->order->id,
                'order_code' => $this->order->order_code,
                'status' => $this->order->status->value,
                'status_label' => $this->order->status->label(),
                'total_amount' => $this->order->total_amount,
            ],
            'payment' => [
                'id' => $this->payment->id,
                'requires_redirect' => $this->requiresRedirect,
                'requires_token' => $this->requiresToken,
            ],
        ];

        if ($this->requiresRedirect && $this->redirectUrl) {
            $result['payment']['redirect_url'] = $this->redirectUrl;
        }

        if ($this->requiresToken) {
            $result['payment']['fincode_id'] = $this->fincodeId;
            $result['payment']['access_id'] = $this->accessId;
        }

        if ($this->cartClearFailed) {
            $result['cart_clear_failed'] = true;
        }

        return $result;
    }
}

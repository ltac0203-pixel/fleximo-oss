<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\PaymentMethod;
use Exception;
use Illuminate\Http\JsonResponse;

class PaymentMethodNotAvailableException extends Exception
{
    public function __construct(
        public readonly PaymentMethod $paymentMethod,
        string $message = ''
    ) {
        parent::__construct($message ?: "決済方法「{$paymentMethod->label()}」は現在利用できません。");
    }

    // 例外をHTTPレスポンスにレンダリングする
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'PAYMENT_METHOD_NOT_AVAILABLE',
            'message' => $this->getMessage(),
            'payment_method' => $this->paymentMethod->value,
        ], 422);
    }
}

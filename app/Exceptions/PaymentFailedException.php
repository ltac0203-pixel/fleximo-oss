<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Payment;
use Exception;

class PaymentFailedException extends Exception
{
    public function __construct(
        public readonly ?Payment $payment = null,
        public readonly ?string $fincodeErrorCode = null,
        string $message = '決済処理に失敗しました',
    ) {
        parent::__construct($message);
    }

    // HTTPレスポンスとしてレンダリングする
    public function render()
    {
        return response()->json([
            'error' => 'PAYMENT_FAILED',
            'message' => $this->getUserMessage(),
            'fincode_error_code' => $this->fincodeErrorCode,
            'payment_id' => $this->payment?->id,
        ], 422);
    }

    public function getUserMessage(): string
    {
        return match ($this->fincodeErrorCode) {
            'E01100101' => 'カード番号が正しくありません。',
            'E01100102' => 'カード有効期限が正しくありません。',
            'E01100103' => 'セキュリティコードが正しくありません。',
            'E01100104' => 'カード名義人が正しくありません。',
            'E01100201' => 'カードが利用できません。別のカードをお試しください。',
            'E01100202' => 'カードの有効期限が切れています。',
            'E01100203' => 'ご利用限度額を超えています。',
            'E01100301' => '決済がキャンセルされました。',
            'E01100302' => '決済が拒否されました。',
            default => $this->getMessage(),
        };
    }
}

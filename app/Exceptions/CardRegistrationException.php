<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CardRegistrationException extends Exception
{
    public function __construct(
        public readonly ?string $fincodeErrorCode = null,
        string $message = 'カード操作に失敗しました',
        public readonly bool $tokenConsumed = false,
    ) {
        parent::__construct($message);
    }

    // HTTPレスポンスとしてレンダリングする
    public function render()
    {
        return response()->json([
            'error' => [
                'message' => $this->getUserMessage(),
            ],
        ], 400);
    }

    public function getUserMessage(): string
    {
        return match ($this->fincodeErrorCode) {
            'E01100101' => 'カード番号が正しくありません。',
            'E01100102' => 'カード有効期限が正しくありません。',
            'E01100103' => 'セキュリティコードが正しくありません。',
            'E01100104' => 'カード名義人が正しくありません。',
            'E01100201' => 'このカードは利用できません。別のカードをお試しください。',
            'E01100202' => 'カードの有効期限が切れています。',
            'E01100203' => 'ご利用限度額を超えています。',
            'E01200101' => 'カードトークンが無効です。もう一度お試しください。',
            'E01100001' => '一時的なエラーが発生しました。もう一度お試しください。',
            default => $this->getMessage(),
        };
    }
}

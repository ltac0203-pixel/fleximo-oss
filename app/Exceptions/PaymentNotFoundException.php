<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

// 決済が見つからない場合の例外
class PaymentNotFoundException extends Exception
{
    public function __construct(
        string $message = '決済が見つかりません。',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    // fincode ID から例外を生成
    public static function forFincodeId(string $fincodeId): self
    {
        return new self('決済が見つかりません。(fincode_idは非表示)');
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Fincode;

use Exception;
use Throwable;

class FincodeApiException extends Exception
{
    public function __construct(
        public readonly ?string $errorCode,
        public readonly array $response,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        if (empty($message) && $errorCode) {
            $message = "fincode API error: {$errorCode}";
        } elseif (empty($message)) {
            $message = 'fincode API error';
        }

        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    // リトライ可能なエラーかどうかを判定する
    public function isRetryable(): bool
    {
        // 一時的な障害（ネットワーク・タイムアウト）は再試行で回復する可能性が高いため、リトライ対象とする
        $retryableCodes = [
            'E01100001', // サーバーエラー
            'E01100002', // タイムアウト
        ];

        if (in_array($this->errorCode, $retryableCodes, true)) {
            return true;
        }

        // HTTP 5xx レスポンスもリトライ対象
        $httpStatus = $this->getCode();

        return $httpStatus >= 500 && $httpStatus < 600;
    }

    // HTTPレスポンスとしてレンダリングする
    public function render()
    {
        return response()->json([
            'error' => 'FINCODE_API_ERROR',
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
        ], 500);
    }
}

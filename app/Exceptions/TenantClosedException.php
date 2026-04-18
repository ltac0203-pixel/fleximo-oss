<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TenantClosedException extends Exception
{
    public function __construct(string $message = '店舗が営業時間外のため、注文を受け付けできません。')
    {
        parent::__construct($message);
    }

    // 例外をHTTPレスポンスにレンダリングする
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'TENANT_CLOSED',
            'message' => $this->getMessage(),
        ], 422);
    }
}

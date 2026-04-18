<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class OrderPausedException extends Exception
{
    public function __construct(string $message = '現在、注文の受付を一時停止しています。しばらくしてからお試しください。')
    {
        parent::__construct($message);
    }

    // 例外をHTTPレスポンスにレンダリングする
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'ORDER_PAUSED',
            'message' => $this->getMessage(),
        ], 422);
    }
}

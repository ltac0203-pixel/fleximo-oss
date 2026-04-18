<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class EmptyCartException extends Exception
{
    public function __construct(string $message = 'カートが空です。')
    {
        parent::__construct($message);
    }

    // 例外をHTTPレスポンスにレンダリングする
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'EMPTY_CART',
            'message' => $this->getMessage(),
        ], 422);
    }
}

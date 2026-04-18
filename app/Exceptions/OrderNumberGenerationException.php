<?php

declare(strict_types=1);

namespace App\Exceptions;

use Carbon\Carbon;
use Exception;

class OrderNumberGenerationException extends Exception
{
    public function __construct(
        public readonly int $tenantId,
        public readonly Carbon $businessDate,
        public readonly string $reason,
        string $message = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        if (empty($message)) {
            $message = "注文番号の生成に失敗しました。テナントID: {$tenantId}, 営業日: {$businessDate->toDateString()}, 理由: {$reason}";
        }

        parent::__construct($message, $code, $previous);
    }

    public function render()
    {
        return response()->json([
            'error' => 'ORDER_NUMBER_GENERATION_FAILED',
            'message' => $this->getMessage(),
            'tenant_id' => $this->tenantId,
            'business_date' => $this->businessDate->toDateString(),
            'reason' => $this->reason,
        ], 500);
    }
}

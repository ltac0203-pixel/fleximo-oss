<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\OrderStatus;
use Exception;

class InvalidStatusTransitionException extends Exception
{
    public function __construct(
        public readonly OrderStatus $currentStatus,
        public readonly OrderStatus $targetStatus,
        string $message = '',
    ) {
        $message = $message ?: "Cannot transition from {$currentStatus->value} to {$targetStatus->value}";
        parent::__construct($message);
    }

    // HTTPレスポンスとしてレンダリングする
    public function render()
    {
        return response()->json([
            'error' => 'INVALID_STATUS_TRANSITION',
            'message' => $this->getUserMessage(),
            'current_status' => $this->currentStatus->value,
            'target_status' => $this->targetStatus->value,
        ], 422);
    }

    public function getUserMessage(): string
    {
        return "「{$this->currentStatus->label()}」から「{$this->targetStatus->label()}」への変更はできません。";
    }
}

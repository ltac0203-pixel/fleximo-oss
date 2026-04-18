<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InvalidOptionSelectionException extends Exception
{
    public function __construct(
        public readonly string $optionGroupName,
        string $message = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        if (empty($message)) {
            $message = "オプショングループ「{$optionGroupName}」の選択が無効です。";
        }

        parent::__construct($message, $code, $previous);
    }

    public function render()
    {
        return response()->json([
            'error' => 'INVALID_OPTION_SELECTION',
            'message' => $this->getMessage(),
            'option_group_name' => $this->optionGroupName,
        ], 422);
    }
}

<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\MenuItem;
use Exception;

class ItemNotAvailableException extends Exception
{
    public function __construct(
        public readonly MenuItem $menuItem,
        string $message = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        if (empty($message)) {
            $message = "商品「{$menuItem->name}」は現在販売されていません。";
        }

        parent::__construct($message, $code, $previous);
    }

    public function render()
    {
        return response()->json([
            'error' => 'ITEM_NOT_AVAILABLE',
            'message' => $this->getMessage(),
            'menu_item_id' => $this->menuItem->id,
            'menu_item_name' => $this->menuItem->name,
        ], 422);
    }
}

<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\MenuCategory;
use Exception;

class CategoryHasItemsException extends Exception
{
    public function __construct(
        public readonly MenuCategory $category,
        string $message = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        if (empty($message)) {
            $message = "カテゴリ「{$category->name}」には商品が紐付いています。先に商品の紐付けを解除してください。";
        }

        parent::__construct($message, $code, $previous);
    }

    public function render()
    {
        return response()->json([
            'error' => 'CATEGORY_HAS_ITEMS',
            'message' => $this->getMessage(),
            'category_id' => $this->category->id,
            'category_name' => $this->category->name,
        ], 409);
    }
}

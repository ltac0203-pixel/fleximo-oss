<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopItemResource extends JsonResource
{
    // リソースを配列に変換する。
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->resource['rank'],
            'menu_item_id' => $this->resource['menu_item_id'],
            'name' => $this->resource['name'],
            'quantity' => $this->resource['quantity'],
            'revenue' => $this->resource['revenue'],
        ];
    }
}

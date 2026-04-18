<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesDataResource extends JsonResource
{
    // リソースを配列に変換する。
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->resource['date'],
            'sales' => $this->resource['total_sales'],
            'orders' => $this->resource['order_count'],
        ];
    }
}

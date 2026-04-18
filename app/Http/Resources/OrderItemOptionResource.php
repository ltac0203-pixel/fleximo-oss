<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @property int $id
// @property int $order_item_id
// @property int|null $option_id
// @property string $name
// @property int $price
// @property \Carbon\Carbon $created_at
class OrderItemOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'option_id' => $this->option_id,
            'name' => $this->name,
            'price' => $this->price,
            'created_at' => $this->created_at,
        ];
    }
}

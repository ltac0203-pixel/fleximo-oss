<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @property int $id
// @property int $order_id
// @property int|null $menu_item_id
// @property string $name
// @property int $price
// @property int $quantity
// @property int $subtotal
// @property \Illuminate\Database\Eloquent\Collection $options
// @property \Carbon\Carbon $created_at
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menu_item_id' => $this->menu_item_id,
            'name' => $this->name,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'options' => OrderItemOptionResource::collection($this->whenLoaded('options')),
            'subtotal' => $this->subtotal,
            'created_at' => $this->created_at,
        ];
    }
}

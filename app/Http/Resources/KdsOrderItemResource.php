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
class KdsOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'options' => $this->whenLoaded('options', function () {
                return $this->options->map(fn ($option) => [
                    'name' => $option->option->name ?? $option->name,
                    'price' => $option->price,
                ]);
            }),
        ];
    }
}

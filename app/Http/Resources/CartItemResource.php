<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @property int $id
// @property int $quantity
// @property int $subtotal
// @property \App\Models\MenuItem $menuItem
// @property \Illuminate\Database\Eloquent\Collection $options
// @property \Carbon\Carbon $created_at
// @property \Carbon\Carbon $updated_at
class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menu_item' => new MenuItemResource($this->whenLoaded('menuItem')),
            'quantity' => $this->quantity,
            'options' => $this->whenLoaded('options', function () {
                return OptionResource::collection(
                    $this->options->map(fn ($cartItemOption) => $cartItemOption->option)->filter()
                );
            }),
            'subtotal' => $this->subtotal,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

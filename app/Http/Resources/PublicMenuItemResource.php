<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @property int $id
// @property string $name
// @property string|null $description
// @property int $price
// @property bool $is_sold_out
// @property bool $is_available
// @property \Illuminate\Database\Eloquent\Collection $optionGroups
class PublicMenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'is_sold_out' => $this->is_sold_out,
            'is_available' => $this->isAvailableNow(),
            'option_groups' => PublicOptionGroupResource::collection($this->whenLoaded('optionGroups')),
        ];
    }
}

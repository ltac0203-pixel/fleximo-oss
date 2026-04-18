<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @property int $id
// @property string $name
// @property string|null $description
// @property int $price
// @property bool $is_active
// @property bool $is_sold_out
// @property string|null $available_from
// @property string|null $available_until
// @property int $available_days
// @property int $sort_order
// @property \Illuminate\Database\Eloquent\Collection $categories
// @property \Illuminate\Database\Eloquent\Collection $optionGroups
class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'is_sold_out' => $this->is_sold_out,
            'available_from' => $this->available_from,
            'available_until' => $this->available_until,
            'available_days' => $this->available_days,
            'sort_order' => $this->sort_order,
            'allergens' => $this->allergens,
            'allergen_advisories' => $this->allergen_advisories,
            'allergen_labels' => $this->getAllergenLabels(),
            'advisory_labels' => $this->getAdvisoryLabels(),
            'allergen_note' => $this->allergen_note,
            'nutrition_info' => $this->nutrition_info,
            'categories' => MenuCategoryResource::collection($this->whenLoaded('categories')),
            'option_groups' => OptionGroupResource::collection($this->whenLoaded('optionGroups')),
            'is_available' => $this->isAvailableNow(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @property int $id
// @property string $name
// @property int $price
// @property int $sort_order
// @property bool $is_active
class OptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'option_group_id' => $this->option_group_id,
            'name' => $this->name,
            'price' => $this->price,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}

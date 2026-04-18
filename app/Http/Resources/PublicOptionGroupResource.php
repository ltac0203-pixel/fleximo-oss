<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @property int $id
// @property string $name
// @property bool $required
// @property int $min_select
// @property int $max_select
// @property \Illuminate\Database\Eloquent\Collection $options
class PublicOptionGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'required' => $this->required,
            'min_select' => $this->min_select,
            'max_select' => $this->max_select,
            'options' => PublicOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}

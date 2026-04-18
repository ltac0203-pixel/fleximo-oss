<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @property int $id
// @property int $user_id
// @property int $tenant_id
// @property \Illuminate\Database\Eloquent\Collection $items
// @property int $total
// @property int $item_count
// @property \Carbon\Carbon $created_at
// @property \Carbon\Carbon $updated_at
class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'tenant_id' => $this->tenant_id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'total' => $this->total,
            'item_count' => $this->item_count,
            'is_empty' => $this->isEmpty(),
            'tenant' => $this->whenLoaded('tenant', function () {
                $status = $this->tenant->getBusinessStatus();

                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                    'slug' => $this->tenant->slug,
                    'is_open' => $status['is_open'],
                    'today_business_hours' => $status['today_business_hours'],
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

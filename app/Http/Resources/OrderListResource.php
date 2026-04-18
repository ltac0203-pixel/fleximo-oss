<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @mixin Order
class OrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
            ]),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'total_amount' => $this->total_amount,
            'created_at' => $this->created_at,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @mixin \App\Models\Order
class CustomerOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'tenant_name' => $this->whenLoaded('tenant', fn () => $this->tenant->name),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'total_amount' => $this->total_amount,
            'payment' => $this->whenLoaded('payment', fn () => [
                'method' => $this->payment?->method?->value,
                'method_label' => $this->payment?->method?->label(),
                'status' => $this->payment?->status,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ])),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

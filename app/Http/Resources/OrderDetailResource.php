<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @mixin Order
class OrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'business_date' => $this->business_date->format('Y-m-d'),
            'tenant' => [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
                'slug' => $this->tenant->slug,
                'address' => $this->tenant->address,
            ],
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'total_amount' => $this->total_amount,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payment' => $this->whenLoaded('payment', fn () => [
                'method' => $this->payment->method->value,
                'method_label' => $this->payment->method->label(),
                'status' => $this->payment->status->value,
                'status_label' => $this->payment->status->label(),
            ]),
            'paid_at' => $this->paid_at,
            'accepted_at' => $this->accepted_at,
            'in_progress_at' => $this->in_progress_at,
            'ready_at' => $this->ready_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
        ];
    }
}

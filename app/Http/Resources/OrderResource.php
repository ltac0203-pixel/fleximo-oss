<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @property int $id
// @property int $user_id
// @property int $tenant_id
// @property string $order_code
// @property \Carbon\Carbon $business_date
// @property OrderStatus $status
// @property int $total_amount
// @property int|null $payment_id
// @property \Carbon\Carbon|null $paid_at
// @property \Carbon\Carbon|null $accepted_at
// @property \Carbon\Carbon|null $in_progress_at
// @property \Carbon\Carbon|null $ready_at
// @property \Carbon\Carbon|null $completed_at
// @property \Carbon\Carbon|null $cancelled_at
// @property \Carbon\Carbon $created_at
// @property \Carbon\Carbon $updated_at
// @property \Illuminate\Database\Eloquent\Collection $items
// @property \App\Models\User $user
// @property \App\Models\Tenant $tenant
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'tenant_id' => $this->tenant_id,
            'order_code' => $this->order_code,
            'business_date' => $this->business_date->format('Y-m-d'),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'total_amount' => $this->total_amount,
            'can_be_cancelled' => $this->canBeCancelled(),
            'is_active' => $this->isActive(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'user' => $this->whenLoaded('user', function () use ($request) {
                $data = [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];

                // 個人情報保護のため、他人のメールアドレスが漏洩しないよう所有者本人にのみ公開する
                if ($request->user()?->id === $this->user_id) {
                    $data['email'] = $this->user->email;
                }

                return $data;
            }),
            'tenant' => new TenantResource($this->whenLoaded('tenant')),
            'paid_at' => $this->paid_at,
            'accepted_at' => $this->accepted_at,
            'in_progress_at' => $this->in_progress_at,
            'ready_at' => $this->ready_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

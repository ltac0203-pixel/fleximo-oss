<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @mixin \App\Models\User
class CustomerDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'account_status' => $this->account_status?->value ?? 'active',
            'account_status_label' => $this->account_status?->label() ?? 'アクティブ',
            'account_status_color' => $this->account_status?->color() ?? 'green',
            'account_status_reason' => $this->account_status_reason,
            'account_status_changed_at' => $this->account_status_changed_at?->toISOString(),
            'account_status_changed_by' => $this->whenLoaded('accountStatusChangedBy', fn () => [
                'id' => $this->accountStatusChangedBy->id,
                'name' => $this->accountStatusChangedBy->name,
            ]),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'total_orders' => (int) ($this->total_orders ?? 0),
            'total_spent' => (int) ($this->total_spent ?? 0),
            'favorite_tenants_count' => (int) ($this->favorite_tenants_count ?? 0),
        ];
    }
}

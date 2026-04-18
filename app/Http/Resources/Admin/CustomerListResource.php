<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @mixin \App\Models\User
class CustomerListResource extends JsonResource
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
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'orders_count' => (int) ($this->orders_count ?? 0),
        ];
    }
}

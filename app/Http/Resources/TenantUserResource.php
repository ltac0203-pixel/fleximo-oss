<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantUserResource extends JsonResource
{
    // リソースを配列に変換する。
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'is_active' => $this->user->is_active,
            ],
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

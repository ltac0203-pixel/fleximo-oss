<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantShopIdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'fincode_shop_id' => $this->fincode_shop_id,
            'status' => $this->status,
            'is_active' => $this->is_active,
        ];
    }
}

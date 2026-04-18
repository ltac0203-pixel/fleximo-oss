<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// テナント基本情報リソース（顧客向け）
// @property int $id
// @property string $name
// @property string $slug
// @property string|null $address
// @property bool $is_open
class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->getBusinessStatus();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'is_open' => $status['is_open'],
            'is_order_paused' => $this->is_order_paused,
            'today_business_hours' => $status['today_business_hours'],
        ];
    }
}

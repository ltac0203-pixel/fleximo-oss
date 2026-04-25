<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Tenant\BusinessHours\BusinessHoursSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// テナント基本情報リソース（顧客向け）
// @property int $id
// @property string $name
// @property string $slug
// @property string|null $address
class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $base = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'is_order_paused' => $this->is_order_paused,
        ];

        // businessHours が eager load されている場合のみ営業状態を返す。
        // 未 load 時に lazy loading が走るのを避け、リレーション load 責務を呼び出し元に明示する。
        if ($this->resource->relationLoaded('businessHours')) {
            $status = (new BusinessHoursSchedule($this->resource->businessHours))->statusAt();
            $base['is_open'] = $status->isOpen;
            $base['today_business_hours'] = $status->todayBusinessHours;
        }

        return $base;
    }
}

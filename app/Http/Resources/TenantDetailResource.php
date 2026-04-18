<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;

// テナント詳細情報リソース（管理者向け）
// @property string|null $email
// @property string|null $phone
class TenantDetailResource extends TenantResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'email' => $this->email,
            'phone' => $this->phone,
            'fincode_shop_id' => $this->fincode_shop_id,
            'is_approved' => $this->is_approved,
            'is_order_paused' => $this->is_order_paused,
            'order_paused_at' => $this->order_paused_at?->toIso8601String(),
            'status' => $this->status,
            'business_hours' => $this->businessHours
                ->map(fn ($hour) => [
                    'weekday' => $hour->weekday,
                    'open_time' => $this->formatBusinessTime($hour->open_time),
                    'close_time' => $this->formatBusinessTime($hour->close_time),
                    'sort_order' => $hour->sort_order,
                ])
                ->values(),
        ]);
    }

    private function formatBusinessTime(string $time): string
    {
        return Carbon::parse($time)->format('H:i');
    }
}

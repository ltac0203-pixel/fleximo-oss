<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// 顧客向けメニューリソース（カテゴリとネストした商品を含む）
// @property int $id
// @property string $name
// @property int $sort_order
// @property \Illuminate\Database\Eloquent\Collection $menuItems
class PublicMenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sort_order' => $this->sort_order,
            'items' => PublicMenuItemResource::collection($this->whenLoaded('menuItems')),
        ];
    }
}

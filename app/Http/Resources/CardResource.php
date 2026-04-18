<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @mixin \App\Models\FincodeCard
class CardResource extends JsonResource
{
    // リソースを配列に変換する。
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'card_no_display' => $this->card_no_display,
            'brand' => $this->brand,
            'expire' => $this->expire_display,
            'is_default' => $this->is_default,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// 【廃止予定】このモデルはランダム注文番号の導入により使用されなくなりました。
// 互換性のため残していますが、新しい注文番号生成ではordersテーブルのユニーク制約で重複を防止します。
class OrderNumberSequence extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'business_date',
        'last_sequence',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'last_sequence' => 'integer',
        ];
    }
}

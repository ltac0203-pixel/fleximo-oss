<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class FincodeCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'fincode_customer_id',
        'fincode_card_id',
        'card_no_display',
        'brand',
        'expire',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function fincodeCustomer(): BelongsTo
    {
        return $this->belongsTo(FincodeCustomer::class);
    }

    // トランザクション + 排他ロックで並行リクエスト時のレースコンディションを防止する
    public function setAsDefault(): void
    {
        DB::transaction(function () {
            // 同じ顧客の全カードを排他ロックしてから更新する
            self::where('fincode_customer_id', $this->fincode_customer_id)
                ->lockForUpdate()
                ->update(['is_default' => false]);

            $this->is_default = true;
            $this->save();
        });
    }

    public function getExpireDisplayAttribute(): string
    {
        if (strlen($this->expire) !== 4) {
            return $this->expire;
        }

        $year = substr($this->expire, 0, 2);
        $month = substr($this->expire, 2, 2);

        return "{$month}/{$year}";
    }
}

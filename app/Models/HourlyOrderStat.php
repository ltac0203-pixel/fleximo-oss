<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Models\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HourlyOrderStat extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'date',
        'hour',
        'order_count',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'date' => 'date',
            'hour' => 'integer',
            'order_count' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    // 日付でフィルタするスコープ
    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('date', $date->toDateString());
    }

    // 日付範囲でフィルタするスコープ
    public function scopeBetweenDates($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
    }

    // 統計を保存または更新する
    public static function updateStats(
        int $tenantId,
        Carbon $date,
        int $hour,
        int $orderCount,
        int $totalAmount
    ): self {
        return self::withoutGlobalScope(TenantScope::class)->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'date' => $date->toDateString(),
                'hour' => $hour,
            ],
            [
                'order_count' => $orderCount,
                'total_amount' => $totalAmount,
            ]
        );
    }

    // 24時間分の統計を一括で保存または更新する
    public static function upsertBatch(int $tenantId, Carbon $date, array $hourlyData): void
    {
        if (empty($hourlyData)) {
            return;
        }

        $records = [];
        $dateString = $date->toDateString();
        $now = now();

        foreach ($hourlyData as $data) {
            $records[] = [
                'tenant_id' => $tenantId,
                'date' => $dateString,
                'hour' => $data['hour'],
                'order_count' => $data['order_count'],
                'total_amount' => $data['total_amount'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        self::withoutGlobalScope(TenantScope::class)->upsert(
            $records,
            ['tenant_id', 'date', 'hour'],
            ['order_count', 'total_amount', 'updated_at']
        );
    }
}

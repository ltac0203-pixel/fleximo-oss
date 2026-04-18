<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetricType;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// 注意: tenant_id が null のプラットフォーム集計を扱うため、BelongsToTenant は使用しない。
// TenantContext が設定されている場合のみ可視性スコープで tenant_id を絞り込む。
// TenantContext 未設定時の参照範囲は呼び出し側で forTenant()/forPlatform() を明示する。
class AnalyticsCache extends Model
{
    use HasFactory;

    private const VISIBILITY_SCOPE = 'analytics_cache_visibility';

    protected $table = 'analytics_cache';

    protected $fillable = [
        'tenant_id',
        'metric_type',
        'date',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'metric_type' => MetricType::class,
            'date' => 'date',
            'data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(self::VISIBILITY_SCOPE, function (Builder $builder): void {
            $table = $builder->getModel()->getTable();
            $tenantId = app(TenantContext::class)->getTenantId();

            if ($tenantId !== null) {
                $builder->where($table.'.tenant_id', $tenantId);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant(Builder $query, ?int $tenantId): Builder
    {
        $query = $query->withoutGlobalScope(self::VISIBILITY_SCOPE);

        if ($tenantId === null) {
            return $query->forPlatform();
        }

        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForPlatform(Builder $query): Builder
    {
        return $query
            ->withoutGlobalScope(self::VISIBILITY_SCOPE)
            ->whereNull('tenant_id');
    }

    public function scopeForAllTenants(Builder $query): Builder
    {
        return $query->withoutGlobalScope(self::VISIBILITY_SCOPE);
    }

    public function scopeOfType(Builder $query, MetricType $type): Builder
    {
        return $query->where('metric_type', $type->value);
    }

    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('date', $date->toDateString());
    }

    public function scopeBetweenDates(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
    }

    public static function saveCache(
        ?int $tenantId,
        MetricType $metricType,
        Carbon $date,
        array $data
    ): self {
        $cache = self::forTenant($tenantId)
            ->ofType($metricType)
            ->forDate($date)
            ->first();

        if ($cache) {
            $cache->data = $data;
            $cache->save();

            return $cache;
        }

        return self::create([
            'tenant_id' => $tenantId,
            'metric_type' => $metricType->value,
            'date' => $date->toDateString(),
            'data' => $data,
        ]);
    }

    public static function getCached(
        ?int $tenantId,
        MetricType $metricType,
        Carbon $date
    ): ?array {
        $cache = self::forTenant($tenantId)
            ->ofType($metricType)
            ->forDate($date)
            ->first();

        return $cache?->data;
    }

    public static function saveCacheBatch(array $entries): void
    {
        if (empty($entries)) {
            return;
        }

        $records = [];
        $now = now();

        foreach ($entries as $entry) {
            $records[] = [
                'tenant_id' => $entry['tenant_id'],
                'metric_type' => $entry['metric_type']->value,
                'date' => $entry['date']->toDateString(),
                'data' => json_encode($entry['data']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        self::upsert(
            $records,
            ['tenant_id', 'metric_type', 'date'],
            ['data', 'updated_at']
        );
    }
}

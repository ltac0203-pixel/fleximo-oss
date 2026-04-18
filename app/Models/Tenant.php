<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantStatus;
use App\Models\Traits\Auditable;
use App\Support\StringHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property TenantStatus|null $status
 * @property \Illuminate\Support\Carbon|null $order_paused_at
 */
class Tenant extends Model
{
    use Auditable, HasFactory;

    private array $businessStatusCache = [];

    // status, is_approved, is_active, is_order_paused, order_paused_at は
    // テナント承認フローまたは運用制御フローでのみ変更されるべきフィールドのため
    // $fillableから除外し、Service層では直接属性代入で書き込む。
    protected $fillable = [
        'name',
        'slug',
        'address',
        'email',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_order_paused' => 'boolean',
            'order_paused_at' => 'datetime',
            'is_approved' => 'boolean',
            'status' => TenantStatus::class,
            'platform_fee_rate_bps' => 'integer',
        ];
    }

    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function businessHours(): HasMany
    {
        return $this->hasMany(TenantBusinessHour::class)
            ->orderBy('weekday')
            ->orderBy('sort_order');
    }

    public function admins(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            TenantUser::class,
            'tenant_id',
            'id',
            'id',
            'user_id'
        )->where('tenant_users.role', \App\Enums\TenantUserRole::Admin);
    }

    public function staff(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            TenantUser::class,
            'tenant_id',
            'id',
            'id',
            'user_id'
        )->where('tenant_users.role', \App\Enums\TenantUserRole::Staff);
    }

    public function allStaff(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            TenantUser::class,
            'tenant_id',
            'id',
            'id',
            'user_id'
        );
    }

    public function isActive(): bool
    {
        return $this->is_active && $this->status === TenantStatus::Active;
    }

    public function isApproved(): bool
    {
        return (bool) $this->is_approved;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('status', TenantStatus::Active);
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (empty($keyword)) {
            return $query;
        }

        $escaped = StringHelper::escapeLike($keyword);

        return $query->where(function (Builder $q) use ($escaped) {
            $q->where('name', 'like', "%{$escaped}%")
                ->orWhere('address', 'like', "%{$escaped}%");
        });
    }

    // 複数の営業時間帯と日またぎ（深夜帯）に対応するため、前日の営業時間も考慮し1回の走査で完結させる
    public function getBusinessStatus(?Carbon $time = null): array
    {
        $time = $time?->copy() ?? Carbon::now();
        $cacheKey = $time->format('Y-m-d H:i:s');

        if (array_key_exists($cacheKey, $this->businessStatusCache)) {
            return $this->businessStatusCache[$cacheKey];
        }

        $businessHours = $this->businessHours;

        if ($businessHours->isEmpty()) {
            return $this->businessStatusCache[$cacheKey] = ['is_open' => false, 'today_business_hours' => []];
        }

        $currentTime = $time->format('H:i');
        $weekday = $time->dayOfWeek;
        $previousWeekday = ($weekday + 6) % 7;

        $isOpen = false;
        $todayHours = [];

        // 前日の深夜営業チェック
        foreach ($businessHours->where('weekday', $previousWeekday)->sortBy('sort_order') as $hour) {
            $openTime = $this->formatTime($hour->open_time);
            $closeTime = $this->formatTime($hour->close_time);
            if ($this->isOvernight($openTime, $closeTime)) {
                $todayHours[] = ['open_time' => '00:00', 'close_time' => $closeTime];
                if (! $isOpen && $currentTime < $closeTime) {
                    $isOpen = true;
                }
            }
        }

        // 当日の営業時間チェック
        foreach ($businessHours->where('weekday', $weekday)->sortBy('sort_order') as $hour) {
            $openTime = $this->formatTime($hour->open_time);
            $closeTime = $this->formatTime($hour->close_time);
            $todayHours[] = ['open_time' => $openTime, 'close_time' => $closeTime];
            if (! $isOpen && $this->isWithinTodayTimeRange($currentTime, $openTime, $closeTime)) {
                $isOpen = true;
            }
        }

        return $this->businessStatusCache[$cacheKey] = ['is_open' => $isOpen, 'today_business_hours' => $todayHours];
    }

    public function pauseOrders(): void
    {
        $this->is_order_paused = true;
        $this->order_paused_at = now();
        $this->save();
    }

    public function resumeOrders(): void
    {
        $this->is_order_paused = false;
        $this->order_paused_at = null;
        $this->save();
    }

    public function isOpenAt(Carbon $time): bool
    {
        $businessHours = $this->businessHours;

        // 未設定は「営業時間を決めていない＝開店していない」と解釈し、安全側に倒す
        if ($businessHours->isEmpty()) {
            return false;
        }

        $currentTime = $time->format('H:i');
        $weekday = $time->dayOfWeek;
        $previousWeekday = ($weekday + 6) % 7;

        // まず当日の曜日設定をチェックし、深夜帯は翌日の前日分で処理するため当日側はopen以降のみ判定
        foreach ($businessHours->where('weekday', $weekday) as $hour) {
            $openTime = $this->formatTime($hour->open_time);
            $closeTime = $this->formatTime($hour->close_time);

            if ($this->isWithinTodayTimeRange($currentTime, $openTime, $closeTime)) {
                return true;
            }
        }

        // 日をまたぐ深夜営業（例: 22:00-02:00）を正しく判定するため、前日の設定も確認する
        foreach ($businessHours->where('weekday', $previousWeekday) as $hour) {
            $openTime = $this->formatTime($hour->open_time);
            $closeTime = $this->formatTime($hour->close_time);

            if ($this->isOvernight($openTime, $closeTime) && $currentTime < $closeTime) {
                return true;
            }
        }

        return false;
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->getBusinessStatus()['is_open'];
    }

    public function getTodayBusinessHours(?Carbon $time = null): array
    {
        return $this->getBusinessStatus($time)['today_business_hours'];
    }

    private function formatTime(string $time): string
    {
        return Carbon::parse($time)->format('H:i');
    }

    private function isOvernight(string $openTime, string $closeTime): bool
    {
        return $openTime > $closeTime;
    }

    private function isWithinTodayTimeRange(
        string $currentTime,
        string $openTime,
        string $closeTime
    ): bool {
        if ($openTime <= $closeTime) {
            return $currentTime >= $openTime && $currentTime < $closeTime;
        }

        // 日またぎ営業のclose側は前日分として別途判定されるため、当日分はopen以降のみ対象とする
        return $currentTime >= $openTime;
    }
}

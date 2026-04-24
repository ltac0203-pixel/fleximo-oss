<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantStatus;
use App\Enums\TenantUserRole;
use App\Models\Traits\Auditable;
use App\Support\StringHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property TenantStatus|null $status
 * @property Carbon|null $order_paused_at
 * @property-read Collection<int, TenantBusinessHour> $businessHours
 */
class Tenant extends Model
{
    use Auditable, HasFactory;

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

    /**
     * @return HasMany<TenantBusinessHour, $this>
     */
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
        )->where('tenant_users.role', TenantUserRole::Admin);
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
        )->where('tenant_users.role', TenantUserRole::Staff);
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
}

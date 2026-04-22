<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Scopes\TenantScope;
use App\Notifications\ResetPasswordNotification;
use App\Services\TenantContext;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property UserRole|null $role
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    // account_status, role, is_active 等の機密フィールドは運用操作の監査対象なので、
    // 通常更新フローから触れないよう mass assignment を禁止する。
    // Service層では直接属性代入で書き込む。
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'last_login_at',
        'onboarding_completed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'account_status' => AccountStatus::class,
            'account_status_changed_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function favoriteTenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'favorite_tenants')
            ->withPivot('created_at');
    }

    public function hasFavoriteTenant(int $tenantId): bool
    {
        return $this->favoriteTenants()->where('tenants.id', $tenantId)->exists();
    }

    public function tenantUser(): HasOne
    {
        return $this->hasOne(TenantUser::class);
    }

    public function tenant(): HasOneThrough
    {
        return $this->hasOneThrough(
            Tenant::class,
            TenantUser::class,
            'user_id',
            'id',
            'id',
            'tenant_id'
        );
    }

    public function isTenantAdmin(): bool
    {
        return $this->role === UserRole::TenantAdmin;
    }

    public function isTenantStaff(): bool
    {
        return $this->role === UserRole::TenantStaff;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function hasTenantRole(): bool
    {
        return $this->role?->isTenantRole() ?? false;
    }

    // 顧客は tenantUser を持たないため、呼び出し側は null を前提にテナント依存処理を分岐する。
    public function getTenantId(): ?int
    {
        return $this->resolveTenantUser()?->tenant_id;
    }

    public function getTenant(): ?Tenant
    {
        return $this->resolveTenantUser()?->tenant;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function shouldShowOnboarding(): bool
    {
        return $this->onboarding_completed_at === null;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // 認証直後の認可や共有データが TenantContext を参照するため、ユーザー割当から先に同期する。
    public function setTenantContext(): void
    {
        $context = app(TenantContext::class);

        $tenantId = $this->getTenantId();
        if ($tenantId !== null) {
            $context->setTenant($tenantId);
        }
    }

    public function accountStatusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_status_changed_by');
    }

    public function isSuspended(): bool
    {
        return $this->account_status === AccountStatus::Suspended;
    }

    public function isBanned(): bool
    {
        return $this->account_status === AccountStatus::Banned;
    }

    public function isAccountRestricted(): bool
    {
        return $this->account_status !== null && $this->account_status->isRestricted();
    }

    // 管理者操作によるアカウント状態遷移を一元化する。監査メタデータはサービス層で付与する。
    public function applyAccountStatus(
        AccountStatus $status,
        ?string $reason,
        int $changedBy,
        bool $isActive,
    ): void {
        $this->forceFill([
            'account_status' => $status,
            'account_status_reason' => $reason,
            'account_status_changed_at' => now(),
            'account_status_changed_by' => $changedBy,
            'is_active' => $isActive,
        ])->save();
    }

    private function resolveTenantUser(): ?TenantUser
    {
        if (! $this->hasTenantRole()) {
            return null;
        }

        $contextTenantId = app(TenantContext::class)->getTenantId();

        if ($this->relationLoaded('tenantUser')) {
            $loaded = $this->getRelation('tenantUser');

            // テナントコンテキスト未設定時は、過去のスコープ付き null キャッシュを信頼しない
            if ($loaded instanceof TenantUser || $contextTenantId !== null) {
                return $loaded;
            }
        }

        $query = $this->tenantUser();

        if ($contextTenantId === null) {
            // ログイン直後はコンテキスト未設定のため、割当確認のみスコープを外して取得する
            $query->withoutGlobalScope(TenantScope::class);
        }

        $tenantUser = $query->first();
        $this->setRelation('tenantUser', $tenantUser);

        return $tenantUser;
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantUserRole;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property TenantUserRole $role
 */
class TenantUser extends Model
{
    use BelongsToTenant, HasFactory;

    // role はMass Assignment攻撃による権限昇格を防止するため$fillableから除外し、
    // Service層で直接属性代入する
    protected $fillable = [
        'tenant_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'role' => TenantUserRole::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === TenantUserRole::Admin;
    }

    public function isStaff(): bool
    {
        return $this->role === TenantUserRole::Staff;
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', TenantUserRole::Admin);
    }

    public function scopeStaff($query)
    {
        return $query->where('role', TenantUserRole::Staff);
    }
}

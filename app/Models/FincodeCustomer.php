<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FincodeCustomer extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'fincode_customer_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(FincodeCard::class);
    }

    // レースコンディションで is_default=true が複数存在する場合に備え、
    // 最後に更新されたカードを返す
    public function defaultCard(): ?FincodeCard
    {
        return $this->cards()
            ->where('is_default', true)
            ->latest('updated_at')
            ->first();
    }

    public static function findByUserAndTenant(User $user, Tenant $tenant): ?self
    {
        return self::where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->first();
    }
}

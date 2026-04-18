<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BusinessType;
use App\Enums\TenantApplicationStatus;
use App\Support\StringHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property TenantApplicationStatus $status
 * @property BusinessType $business_type
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 */
class TenantApplication extends Model
{
    use HasFactory;

    // status, rejection_reason は審査フローでのみ変更されるべきフィールドのため
    // $fillableから除外し、Service層では直接属性代入で書き込む。
    protected $fillable = [
        'application_code',
        'applicant_name',
        'applicant_email',
        'applicant_phone',
        'tenant_name',
        'tenant_address',
        'business_type',
        'internal_notes',
        'reviewed_at',
    ];

    // reviewed_by, created_tenant_id, applicant_user_id は管理者操作でのみ変更されるべきフィールドのため
    // $fillableから除外し、$guarded扱いとする。Service層では forceFill() または直接属性設定で書き込む。

    protected function casts(): array
    {
        return [
            'status' => TenantApplicationStatus::class,
            'business_type' => BusinessType::class,
            'reviewed_at' => 'datetime',
        ];
    }

    // application_code は申請者とのやり取りや審査画面で露出するため、内部 ID ではなく公開用コードを採番する。
    protected static function booted(): void
    {
        static::creating(function (TenantApplication $application) {
            if (empty($application->application_code)) {
                $application->application_code = self::generateApplicationCode();
            }
        });
    }

    public static function generateApplicationCode(): string
    {
        do {
            $code = 'TAPP-'.strtoupper(Str::random(8));
        } while (self::where('application_code', $code)->exists());

        return $code;
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function createdTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'created_tenant_id');
    }

    public function applicantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === TenantApplicationStatus::Pending;
    }

    public function isUnderReview(): bool
    {
        return $this->status === TenantApplicationStatus::UnderReview;
    }

    public function isApproved(): bool
    {
        return $this->status === TenantApplicationStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === TenantApplicationStatus::Rejected;
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, [
            TenantApplicationStatus::Pending,
            TenantApplicationStatus::UnderReview,
        ]);
    }

    public function canBeRejected(): bool
    {
        return in_array($this->status, [
            TenantApplicationStatus::Pending,
            TenantApplicationStatus::UnderReview,
        ]);
    }

    public function canStartReview(): bool
    {
        return $this->status === TenantApplicationStatus::Pending;
    }

    public function scopeStatus(Builder $query, ?TenantApplicationStatus $status): Builder
    {
        if ($status === null) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeSort(Builder $query, ?string $sortBy = 'created_at', string $sortDir = 'desc'): Builder
    {
        $allowedColumns = ['application_code', 'tenant_name', 'applicant_name', 'status', 'created_at'];
        $column = in_array($sortBy, $allowedColumns, true) ? $sortBy : 'created_at';
        $direction = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($column, $direction);
    }

    // 管理画面の部分一致検索で `%` と `_` をワイルドカード解釈させない。
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (empty($keyword)) {
            return $query;
        }

        $escaped = StringHelper::escapeLike($keyword);

        return $query->where(function (Builder $q) use ($escaped) {
            $q->where('application_code', 'like', "%{$escaped}%")
                ->orWhere('applicant_name', 'like', "%{$escaped}%")
                ->orWhere('applicant_email', 'like', "%{$escaped}%")
                ->orWhere('tenant_name', 'like', "%{$escaped}%");
        });
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getBusinessTypeLabelAttribute(): string
    {
        return $this->business_type->label();
    }
}

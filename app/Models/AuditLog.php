<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use BelongsToTenant;

    // 監査ログは created_at のみ使用し、updated_at は不要
    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    // キャスト対象の属性を取得する。
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // 変更内容をまとめて取得する
    public function getChangesAttribute(): ?array
    {
        if ($this->old_values === null && $this->new_values === null && $this->metadata === null) {
            return null;
        }

        $changes = [];

        if ($this->old_values !== null) {
            $changes['old'] = $this->old_values;
        }

        if ($this->new_values !== null) {
            $changes['new'] = $this->new_values;
        }

        if ($this->metadata !== null) {
            $changes['metadata'] = $this->metadata;
        }

        return $changes;
    }

    // 操作を実行したユーザーを取得する。
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 監査対象モデルを取得する（ポリモーフィックリレーション）。
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}

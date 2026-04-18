<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    // 監査ログ記録
    public static function log(
        string|AuditAction $action,
        ?Model $target = null,
        ?array $changes = null,
        ?int $tenantId = null
    ): AuditLog {
        $actionValue = $action instanceof AuditAction ? $action->value : $action;

        return AuditLog::create([
            'user_id' => auth()->id(),
            'tenant_id' => $tenantId ?? $target?->tenant_id ?? null,
            'action' => $actionValue,
            'auditable_type' => $target ? get_class($target) : null,
            'auditable_id' => $target?->id,
            'old_values' => $changes['old'] ?? null,
            'new_values' => $changes['new'] ?? null,
            'metadata' => $changes['metadata'] ?? null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }

    // モデルライフサイクルイベントのログ記録
    public static function logModelEvent(
        string $event,  // 'created', 'updated', 'deleted'
        Model $model,
        ?array $oldAttributes = null
    ): AuditLog {
        $changes = null;

        if ($event === 'updated' && $oldAttributes) {
            $hidden = $model->getHidden();
            $filteredOld = array_diff_key($oldAttributes, array_flip($hidden));
            $changes = [
                'old' => $filteredOld,
                'new' => self::getSafeAttributes($model),
            ];
        } elseif ($event === 'created') {
            $changes = ['new' => self::getSafeAttributes($model)];
        } elseif ($event === 'deleted') {
            $changes = ['old' => self::getSafeAttributes($model)];
        }

        return self::log(
            action: get_class($model).'.'.$event,
            target: $model,
            changes: $changes
        );
    }

    // センシティブフィールド（$hidden属性）を除外した属性を取得
    /** @return array<string, mixed> */
    private static function getSafeAttributes(Model $model): array
    {
        return array_diff_key($model->getAttributes(), array_flip($model->getHidden()));
    }
}

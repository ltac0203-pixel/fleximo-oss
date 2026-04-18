<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Services\AuditLogger;

trait Auditable
{
    // デフォルトで監査ログから除外する機密情報
    protected array $defaultAuditExclude = [
        'password',
        'password_hash',
        'remember_token',
        'credentials',
        'api_key',
        'secret',
        'api_secret',
    ];

    // 監査ログに記録するイベント（デフォルト）
    protected array $auditableEvents = ['created', 'updated', 'deleted'];

    // モデル固有の除外カラム（オプション）
    protected array $auditExclude = [];

    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            if ($model->shouldAuditEvent('created')) {
                AuditLogger::logModelEvent('created', $model);
            }
        });

        static::updated(function ($model) {
            if ($model->shouldAuditEvent('updated')) {
                $oldAttributes = $model->getOriginal();
                $filtered = $model->filterSensitiveData($oldAttributes);
                AuditLogger::logModelEvent('updated', $model, $filtered);
            }
        });

        static::deleted(function ($model) {
            if ($model->shouldAuditEvent('deleted')) {
                AuditLogger::logModelEvent('deleted', $model);
            }
        });
    }

    // 指定されたイベントを監査ログに記録するか判定
    protected function shouldAuditEvent(string $event): bool
    {
        return in_array($event, $this->auditableEvents);
    }

    // 機密情報をデータから除外
    protected function filterSensitiveData(array $data): array
    {
        $exclude = array_merge(
            $this->defaultAuditExclude,
            $this->auditExclude
        );

        return array_diff_key($data, array_flip($exclude));
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Menu\Concerns;

use App\Enums\AuditAction;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait MenuServiceHelpers
{
    protected function resolveSortOrder(?int $requestedSortOrder, callable $maxSortOrderResolver): int
    {
        if ($requestedSortOrder !== null) {
            return $requestedSortOrder;
        }

        $maxSortOrder = (int) ($maxSortOrderResolver() ?? 0);

        return $maxSortOrder + 1;
    }

    // メニューキャッシュの無効化
    public function invalidateMenuCache(int $tenantId): void
    {
        try {
            Cache::forget("tenant:{$tenantId}:menu");
            Cache::forget("tenant:{$tenantId}:categories");
            Cache::forget("tenant:{$tenantId}:option_groups");
        } catch (\Throwable $e) {
            Log::warning('メニューキャッシュの無効化に失敗しました', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // 監査ログを安全に記録する（失敗してもメイン処理を中断しない）
    protected function safeAuditLog(AuditAction $action, ?Model $model = null, ?array $context = null, ?int $tenantId = null): void
    {
        try {
            AuditLogger::log($action, $model, $context, $tenantId);
        } catch (\Throwable $e) {
            Log::error('監査ログの記録に失敗しました', [
                'action' => $action->value,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

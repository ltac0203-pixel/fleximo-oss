<?php

declare(strict_types=1);

namespace App\Services\Menu\Concerns;

use App\Enums\AuditAction;
use App\Services\AuditLogger;
use App\Support\MenuCacheKeys;
use Illuminate\Database\Eloquent\Model;
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

    public function invalidateMenuCache(int $tenantId): void
    {
        MenuCacheKeys::invalidate($tenantId);
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

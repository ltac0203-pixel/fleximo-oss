<?php

declare(strict_types=1);

namespace App\Services\Menu\Concerns;

use App\Enums\AuditAction;
use App\Services\AuditLogger;
use App\Support\MenuCacheKeys;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

    /**
     * メニュー変更操作の共通テンプレート。
     * DB::transaction でラップし、operation 実行後に safeAuditLog → invalidateMenuCache を呼ぶ。
     * operation の戻り値が Model なら audit 対象として渡す（auditModel 引数明示で上書き可能）。
     *
     * @template T
     *
     * @param  callable(): T  $operation
     * @param  (callable(T): (array<string, mixed>|null))|null  $auditContextResolver  操作後の状態から監査コンテキストを生成
     * @param  (callable(T): ?Model)|null  $auditModelResolver  監査対象モデルを明示的に指定（operation 戻り値が Model でない場合用）
     * @return T
     */
    protected function withMenuMutation(
        AuditAction $action,
        int $tenantId,
        callable $operation,
        ?callable $auditContextResolver = null,
        ?callable $auditModelResolver = null,
    ): mixed {
        return DB::transaction(function () use ($action, $tenantId, $operation, $auditContextResolver, $auditModelResolver) {
            $result = $operation();

            $auditContext = $auditContextResolver !== null ? $auditContextResolver($result) : null;
            $auditModel = $auditModelResolver !== null
                ? $auditModelResolver($result)
                : ($result instanceof Model ? $result : null);

            $this->safeAuditLog($action, $auditModel, $auditContext, $tenantId);
            $this->invalidateMenuCache($tenantId);

            return $result;
        });
    }

    // メニューキャッシュの無効化
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

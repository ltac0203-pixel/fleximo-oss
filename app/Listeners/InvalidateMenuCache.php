<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TenantMenuUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * メニューキャッシュ無効化リスナー
 */
class InvalidateMenuCache
{
    public function handle(TenantMenuUpdated $event): void
    {
        try {
            Cache::forget("tenant:{$event->tenantId}:menu");
            Cache::forget("tenant:{$event->tenantId}:categories");
            Cache::forget("tenant:{$event->tenantId}:option_groups");
        } catch (\Throwable $e) {
            Log::warning('メニューキャッシュの無効化に失敗しました', [
                'tenant_id' => $event->tenantId,
                'change_type' => $event->changeType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

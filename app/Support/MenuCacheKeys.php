<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class MenuCacheKeys
{
    public static function menu(int $tenantId): string
    {
        return "tenant:{$tenantId}:menu";
    }

    public static function categories(int $tenantId): string
    {
        return "tenant:{$tenantId}:categories";
    }

    public static function optionGroups(int $tenantId): string
    {
        return "tenant:{$tenantId}:option_groups";
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function invalidate(int $tenantId, array $context = []): void
    {
        try {
            Cache::forget(self::menu($tenantId));
            Cache::forget(self::categories($tenantId));
            Cache::forget(self::optionGroups($tenantId));
        } catch (\Throwable $e) {
            Log::warning('メニューキャッシュの無効化に失敗しました', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                ...$context,
            ]);
        }
    }
}

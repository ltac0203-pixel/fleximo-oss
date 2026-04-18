<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\TenantMenuUpdated;
use App\Listeners\InvalidateMenuCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InvalidateMenuCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgets_menu_related_cache_keys(): void
    {
        Cache::spy();

        $tenantId = 42;
        $event = new TenantMenuUpdated($tenantId, 'updated');

        $listener = new InvalidateMenuCache;
        $listener->handle($event);

        Cache::shouldHaveReceived('forget')->with("tenant:{$tenantId}:menu")->once();
        Cache::shouldHaveReceived('forget')->with("tenant:{$tenantId}:categories")->once();
        Cache::shouldHaveReceived('forget')->with("tenant:{$tenantId}:option_groups")->once();
    }

    public function test_logs_warning_when_cache_forget_fails(): void
    {
        Cache::shouldReceive('forget')
            ->once()
            ->andThrow(new \RuntimeException('Redis connection refused'));

        \Illuminate\Support\Facades\Log::spy();

        $tenantId = 99;
        $event = new TenantMenuUpdated($tenantId, 'deleted');

        $listener = new InvalidateMenuCache;
        $listener->handle($event);

        \Illuminate\Support\Facades\Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($tenantId): bool {
                return $message === 'メニューキャッシュの無効化に失敗しました'
                    && ($context['tenant_id'] ?? null) === $tenantId
                    && ($context['change_type'] ?? null) === 'deleted'
                    && ($context['error'] ?? null) === 'Redis connection refused';
            });
    }
}

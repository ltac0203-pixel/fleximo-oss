<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\TenantOrderPaused;
use App\Listeners\LogOrderPauseToggled;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogOrderPauseToggledTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_order_paused(): void
    {
        Log::spy();

        $tenant = Tenant::factory()->create();

        $event = new TenantOrderPaused($tenant, true);

        $listener = new LogOrderPauseToggled;
        $listener->handle($event);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($tenant): bool {
                return $message === 'テナントの注文受付状態が変更されました'
                    && ($context['tenant_id'] ?? null) === $tenant->id
                    && ($context['tenant_name'] ?? null) === $tenant->name
                    && ($context['is_paused'] ?? null) === true;
            });
    }

    public function test_logs_order_resumed(): void
    {
        Log::spy();

        $tenant = Tenant::factory()->create();

        $event = new TenantOrderPaused($tenant, false);

        $listener = new LogOrderPauseToggled;
        $listener->handle($event);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($tenant): bool {
                return $message === 'テナントの注文受付状態が変更されました'
                    && ($context['tenant_id'] ?? null) === $tenant->id
                    && ($context['tenant_name'] ?? null) === $tenant->name
                    && ($context['is_paused'] ?? null) === false;
            });
    }
}

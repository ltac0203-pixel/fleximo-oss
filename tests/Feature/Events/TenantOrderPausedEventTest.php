<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Events\TenantOrderPaused;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantOrderPausedEventTest extends TestCase
{
    use RefreshDatabase;

    private TenantService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TenantService::class);
    }

    #[Test]
    public function toggle_order_pause_dispatches_event_when_pausing(): void
    {
        Event::fake([TenantOrderPaused::class]);

        $tenant = Tenant::factory()->create(['is_order_paused' => false]);

        $this->service->toggleOrderPause($tenant);

        Event::assertDispatched(TenantOrderPaused::class, function (TenantOrderPaused $event) use ($tenant) {
            return $event->tenant->id === $tenant->id
                && $event->isPaused === true;
        });
    }

    #[Test]
    public function toggle_order_pause_dispatches_event_when_resuming(): void
    {
        Event::fake([TenantOrderPaused::class]);

        $tenant = Tenant::factory()->create(['is_order_paused' => true]);

        $this->service->toggleOrderPause($tenant);

        Event::assertDispatched(TenantOrderPaused::class, function (TenantOrderPaused $event) use ($tenant) {
            return $event->tenant->id === $tenant->id
                && $event->isPaused === false;
        });
    }

    #[Test]
    public function toggle_order_pause_dispatches_event_exactly_once(): void
    {
        Event::fake([TenantOrderPaused::class]);

        $tenant = Tenant::factory()->create(['is_order_paused' => false]);

        $this->service->toggleOrderPause($tenant);

        Event::assertDispatchedTimes(TenantOrderPaused::class, 1);
    }
}

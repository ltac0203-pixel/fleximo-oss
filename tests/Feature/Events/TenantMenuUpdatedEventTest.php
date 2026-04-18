<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\DTOs\Menu\CreateMenuItemData;
use App\DTOs\Menu\UpdateMenuItemData;
use App\Events\TenantMenuUpdated;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Services\Menu\MenuItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantMenuUpdatedEventTest extends TestCase
{
    use RefreshDatabase;

    private MenuItemService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MenuItemService::class);
    }

    #[Test]
    public function create_dispatches_tenant_menu_updated_with_created_type(): void
    {
        Event::fake([TenantMenuUpdated::class]);

        $tenant = Tenant::factory()->create();

        $data = new CreateMenuItemData(
            name: 'テスト商品',
            price: 500,
            category_ids: [],
        );

        $this->service->create($tenant->id, $data);

        Event::assertDispatched(TenantMenuUpdated::class, function (TenantMenuUpdated $event) use ($tenant) {
            return $event->tenantId === $tenant->id
                && $event->changeType === 'created';
        });
    }

    #[Test]
    public function update_dispatches_tenant_menu_updated_with_updated_type(): void
    {
        Event::fake([TenantMenuUpdated::class]);

        $tenant = Tenant::factory()->create();
        $item = MenuItem::factory()->create(['tenant_id' => $tenant->id]);

        $data = new UpdateMenuItemData(
            name: '更新商品',
            presentFields: ['name'],
        );

        $this->service->update($item, $data);

        Event::assertDispatched(TenantMenuUpdated::class, function (TenantMenuUpdated $event) use ($tenant) {
            return $event->tenantId === $tenant->id
                && $event->changeType === 'updated';
        });
    }

    #[Test]
    public function delete_dispatches_tenant_menu_updated_with_deleted_type(): void
    {
        Event::fake([TenantMenuUpdated::class]);

        $tenant = Tenant::factory()->create();
        $item = MenuItem::factory()->create(['tenant_id' => $tenant->id]);

        $this->service->delete($item);

        Event::assertDispatched(TenantMenuUpdated::class, function (TenantMenuUpdated $event) use ($tenant) {
            return $event->tenantId === $tenant->id
                && $event->changeType === 'deleted';
        });
    }

    #[Test]
    public function toggle_sold_out_dispatches_tenant_menu_updated_with_sold_out_toggled_type(): void
    {
        Event::fake([TenantMenuUpdated::class]);

        $tenant = Tenant::factory()->create();
        $item = MenuItem::factory()->create(['tenant_id' => $tenant->id]);

        $this->service->toggleSoldOut($item);

        Event::assertDispatched(TenantMenuUpdated::class, function (TenantMenuUpdated $event) use ($tenant) {
            return $event->tenantId === $tenant->id
                && $event->changeType === 'sold_out_toggled';
        });
    }

    #[Test]
    public function each_operation_dispatches_event_exactly_once(): void
    {
        Event::fake([TenantMenuUpdated::class]);

        $tenant = Tenant::factory()->create();

        $data = new CreateMenuItemData(
            name: '一回だけ商品',
            price: 300,
            category_ids: [],
        );

        $this->service->create($tenant->id, $data);

        Event::assertDispatchedTimes(TenantMenuUpdated::class, 1);
    }
}

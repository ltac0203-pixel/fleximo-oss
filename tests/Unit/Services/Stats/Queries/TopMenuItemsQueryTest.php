<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Stats\Queries;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Services\Stats\Queries\TopMenuItemsQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopMenuItemsQueryTest extends TestCase
{
    use RefreshDatabase;

    private TopMenuItemsQuery $query;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-03-20 12:00:00'));
        $this->query = app(TopMenuItemsQuery::class);
        $this->tenant = Tenant::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_for_start_date_ranks_items_by_quantity_and_applies_limit(): void
    {
        $coffee = MenuItem::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'コーヒー', 'price' => 500]);
        $latte = MenuItem::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'ラテ', 'price' => 300]);
        $tea = MenuItem::factory()->create(['tenant_id' => $this->tenant->id, 'name' => '紅茶', 'price' => 200]);

        $order = Order::factory()->forTenant($this->tenant)->completed()->forBusinessDate('2026-03-19')->create();
        OrderItem::factory()->for($order)->create(['tenant_id' => $this->tenant->id, 'menu_item_id' => $coffee->id, 'name' => 'コーヒー', 'price' => 500, 'quantity' => 3]);
        OrderItem::factory()->for($order)->create(['tenant_id' => $this->tenant->id, 'menu_item_id' => $latte->id, 'name' => 'ラテ', 'price' => 300, 'quantity' => 5]);
        OrderItem::factory()->for($order)->create(['tenant_id' => $this->tenant->id, 'menu_item_id' => $tea->id, 'name' => '紅茶', 'price' => 200, 'quantity' => 1]);

        $result = $this->query->forStartDate($this->tenant->id, Carbon::parse('2026-03-19'), 2);

        $this->assertCount(2, $result);
        $this->assertSame($latte->id, $result[0]->menu_item_id);
        $this->assertSame(5, (int) $result[0]->total_quantity);
        $this->assertSame($coffee->id, $result[1]->menu_item_id);
    }

    public function test_for_start_date_excludes_non_sales_statuses_and_before_start_date(): void
    {
        $coffee = MenuItem::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'コーヒー', 'price' => 500]);

        $included = Order::factory()->forTenant($this->tenant)->paid()->forBusinessDate('2026-03-19')->create();
        OrderItem::factory()->for($included)->create(['tenant_id' => $this->tenant->id, 'menu_item_id' => $coffee->id, 'name' => 'コーヒー', 'price' => 500, 'quantity' => 2]);

        $cancelled = Order::factory()->forTenant($this->tenant)->cancelled()->forBusinessDate('2026-03-19')->create();
        OrderItem::factory()->for($cancelled)->create(['tenant_id' => $this->tenant->id, 'menu_item_id' => $coffee->id, 'name' => 'コーヒー', 'price' => 500, 'quantity' => 99]);

        $old = Order::factory()->forTenant($this->tenant)->completed()->forBusinessDate('2026-03-18')->create();
        OrderItem::factory()->for($old)->create(['tenant_id' => $this->tenant->id, 'menu_item_id' => $coffee->id, 'name' => 'コーヒー', 'price' => 500, 'quantity' => 99]);

        $result = $this->query->forStartDate($this->tenant->id, Carbon::parse('2026-03-19'), 10);

        $this->assertCount(1, $result);
        $this->assertSame(2, (int) $result[0]->total_quantity);
    }

    public function test_for_start_date_does_not_leak_other_tenant_items(): void
    {
        $myItem = MenuItem::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'ブレンド', 'price' => 400]);
        $myOrder = Order::factory()->forTenant($this->tenant)->completed()->forBusinessDate('2026-03-19')->create();
        OrderItem::factory()->for($myOrder)->create(['tenant_id' => $this->tenant->id, 'menu_item_id' => $myItem->id, 'name' => 'ブレンド', 'price' => 400, 'quantity' => 2]);

        $otherTenant = Tenant::factory()->create();
        $otherItem = MenuItem::factory()->create(['tenant_id' => $otherTenant->id, 'name' => '他商品', 'price' => 500]);
        $otherOrder = Order::factory()->forTenant($otherTenant)->completed()->forBusinessDate('2026-03-19')->create();
        OrderItem::factory()->for($otherOrder)->create(['tenant_id' => $otherTenant->id, 'menu_item_id' => $otherItem->id, 'name' => '他商品', 'price' => 500, 'quantity' => 100]);

        $result = $this->query->forStartDate($this->tenant->id, Carbon::parse('2026-03-19'), 10);

        $this->assertCount(1, $result);
        $this->assertSame($myItem->id, $result[0]->menu_item_id);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_item_can_be_created(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'name' => 'コーヒー',
            'price' => 500,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'name' => 'コーヒー',
            'price' => 500,
            'quantity' => 2,
        ]);
    }

    public function test_belongs_to_order(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertInstanceOf(Order::class, $orderItem->order);
        $this->assertEquals($order->id, $orderItem->order->id);
    }

    public function test_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $orderItem->tenant);
        $this->assertEquals($tenant->id, $orderItem->tenant->id);
    }

    public function test_menu_item_relationship_is_optional(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => null,
        ]);

        $this->assertNull($orderItem->menuItem);
    }

    public function test_menu_item_relationship_works_when_set(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);
        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
        ]);

        $this->assertInstanceOf(MenuItem::class, $orderItem->menuItem);
        $this->assertEquals($menuItem->id, $orderItem->menuItem->id);
    }

    public function test_has_many_options(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
        ]);

        OrderItemOption::factory()->create([
            'order_item_id' => $orderItem->id,
            'tenant_id' => $tenant->id,
        ]);

        OrderItemOption::factory()->create([
            'order_item_id' => $orderItem->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertCount(2, $orderItem->options);
    }

    public function test_subtotal_calculates_correctly_without_options(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'price' => 500,
            'quantity' => 2,
        ]);

        $orderItem->load('options');

        $this->assertEquals(1000, $orderItem->subtotal);
    }

    public function test_subtotal_calculates_correctly_with_options(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'price' => 500,
            'quantity' => 2,
        ]);

        OrderItemOption::factory()->create([
            'order_item_id' => $orderItem->id,
            'tenant_id' => $tenant->id,
            'price' => 100,
        ]);

        OrderItemOption::factory()->create([
            'order_item_id' => $orderItem->id,
            'tenant_id' => $tenant->id,
            'price' => 50,
        ]);

        $orderItem->load('options');

        $this->assertEquals(1300, $orderItem->subtotal);
    }

    public function test_snapshot_price_is_independent_of_menu_item_price(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 500,
        ]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
            'price' => 500,
            'quantity' => 1,
        ]);

        $menuItem->update(['price' => 600]);

        $this->assertEquals(500, $orderItem->fresh()->price);
    }

    public function test_order_item_does_not_have_updated_at(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'price' => 500,
        ]);

        $this->assertNull(OrderItem::UPDATED_AT);
        $this->assertNull($orderItem->updated_at);
    }

    public function test_order_item_options_are_deleted_when_order_item_is_deleted(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
        ]);

        $option1 = OrderItemOption::factory()->create([
            'order_item_id' => $orderItem->id,
            'tenant_id' => $tenant->id,
        ]);

        $option2 = OrderItemOption::factory()->create([
            'order_item_id' => $orderItem->id,
            'tenant_id' => $tenant->id,
        ]);

        $orderItemId = $orderItem->id;
        $orderItem->delete();

        $this->assertDatabaseMissing('order_items', ['id' => $orderItemId]);
        $this->assertDatabaseMissing('order_item_options', ['id' => $option1->id]);
        $this->assertDatabaseMissing('order_item_options', ['id' => $option2->id]);
    }

    public function test_menu_item_id_is_retained_when_menu_item_is_deleted(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);
        $menuItem = MenuItem::factory()->create(['tenant_id' => $tenant->id]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
            'name' => $menuItem->name,
            'price' => $menuItem->price,
        ]);

        $menuItem->delete();

        $orderItem->refresh();

        $this->assertSame($menuItem->id, $orderItem->menu_item_id);
        $this->assertNull($orderItem->menuItem);

        $this->assertNotNull($orderItem->name);
        $this->assertNotNull($orderItem->price);
    }
}

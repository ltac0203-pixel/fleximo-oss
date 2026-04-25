<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Customer;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->customer()->create();
        $this->tenant = Tenant::factory()->create();
    }

    public function test_unauthenticated_cannot_reorder(): void
    {
        $order = $this->createCompletedOrderWithItems();

        $this->postJson("/api/customer/orders/{$order->id}/reorder")
            ->assertUnauthorized();
    }

    public function test_non_customer_cannot_reorder(): void
    {
        $tenantAdmin = User::factory()->tenantAdmin()->create();
        $order = $this->createCompletedOrderWithItems();

        $this->actingAs($tenantAdmin, 'sanctum')
            ->postJson("/api/customer/orders/{$order->id}/reorder")
            ->assertForbidden();
    }

    public function test_cannot_reorder_other_users_order(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $order = $this->createCompletedOrderWithItems(['user_id' => $otherCustomer->id]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/orders/{$order->id}/reorder")
            ->assertForbidden();
    }

    public function test_cannot_reorder_non_completed_order(): void
    {
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
            'available_days' => 127,
        ]);

        $order = Order::factory()->paid()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $menuItem->id,
            'name' => $menuItem->name,
            'price' => $menuItem->price,
            'quantity' => 1,
        ]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/orders/{$order->id}/reorder")
            ->assertForbidden();
    }

    public function test_successful_reorder_adds_items_to_cart(): void
    {
        $order = $this->createCompletedOrderWithItems();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/orders/{$order->id}/reorder");

        $response->assertOk();

        $data = $response->json('data');
        $this->assertNotEmpty($data['added_items']);
        $this->assertEmpty($data['skipped_items']);
        $this->assertEquals($order->items->count(), $data['summary']['items_added']);
        $this->assertEquals(0, $data['summary']['items_skipped']);
        $this->assertNotEmpty($data['cart']);
        $this->assertFalse($data['cart']['is_empty']);
    }

    public function test_skips_deleted_menu_items(): void
    {
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
            'available_days' => 127,
        ]);

        $order = Order::factory()->completed()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);

        // 利用可能なアイテム
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $menuItem->id,
            'name' => $menuItem->name,
            'price' => $menuItem->price,
            'quantity' => 1,
        ]);

        // 削除済みアイテム（menu_item_id = null）
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => null,
            'name' => '削除済み商品',
            'price' => 300,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/orders/{$order->id}/reorder");

        $response->assertOk();

        $data = $response->json('data');
        $this->assertCount(1, $data['added_items']);
        $this->assertCount(1, $data['skipped_items']);
        $this->assertEquals('menu_item_deleted', $data['skipped_items'][0]['reason']);
    }

    public function test_skips_sold_out_items(): void
    {
        $availableItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
            'available_days' => 127,
        ]);

        $soldOutItem = MenuItem::factory()->soldOut()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 800,
            'is_active' => true,
            'available_days' => 127,
        ]);

        $order = Order::factory()->completed()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $availableItem->id,
            'name' => $availableItem->name,
            'price' => $availableItem->price,
            'quantity' => 1,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $soldOutItem->id,
            'name' => $soldOutItem->name,
            'price' => $soldOutItem->price,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/orders/{$order->id}/reorder");

        $response->assertOk();

        $data = $response->json('data');
        $this->assertCount(1, $data['added_items']);
        $this->assertCount(1, $data['skipped_items']);
        $this->assertEquals('sold_out', $data['skipped_items'][0]['reason']);
    }

    public function test_partial_reorder_with_mixed_availability(): void
    {
        $activeItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
            'available_days' => 127,
        ]);

        $inactiveItem = MenuItem::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 600,
            'is_sold_out' => false,
            'available_days' => 127,
        ]);

        $soldOutItem = MenuItem::factory()->soldOut()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 700,
            'is_active' => true,
            'available_days' => 127,
        ]);

        $order = Order::factory()->completed()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $activeItem->id,
            'name' => $activeItem->name,
            'price' => $activeItem->price,
            'quantity' => 2,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $inactiveItem->id,
            'name' => $inactiveItem->name,
            'price' => $inactiveItem->price,
            'quantity' => 1,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $soldOutItem->id,
            'name' => $soldOutItem->name,
            'price' => $soldOutItem->price,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/orders/{$order->id}/reorder");

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals(3, $data['summary']['total_items_in_order']);
        $this->assertEquals(1, $data['summary']['items_added']);
        $this->assertEquals(2, $data['summary']['items_skipped']);
    }

    public function test_detects_price_changes(): void
    {
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 600,
            'is_active' => true,
            'is_sold_out' => false,
            'available_days' => 127,
        ]);

        $order = Order::factory()->completed()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);

        // 注文時は500円だったが、現在は600円
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $menuItem->id,
            'name' => $menuItem->name,
            'price' => 500,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/orders/{$order->id}/reorder");

        $response->assertOk();

        $data = $response->json('data');
        $this->assertCount(1, $data['added_items']);
        $this->assertTrue($data['added_items'][0]['price_changed']);
        $this->assertEquals(500, $data['added_items'][0]['original_unit_price']);
        $this->assertEquals(600, $data['added_items'][0]['current_unit_price']);
    }

    public function test_all_items_unavailable_returns_422(): void
    {
        $soldOutItem = MenuItem::factory()->soldOut()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'available_days' => 127,
        ]);

        $order = Order::factory()->completed()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $soldOutItem->id,
            'name' => $soldOutItem->name,
            'price' => $soldOutItem->price,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/orders/{$order->id}/reorder");

        $response->assertStatus(422);
    }

    public function test_preserves_existing_cart_items(): void
    {
        $existingMenuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 300,
            'is_active' => true,
            'is_sold_out' => false,
            'available_days' => 127,
        ]);

        $reorderMenuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
            'available_days' => 127,
        ]);

        // 先にカートに商品を追加
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/customer/cart/items', [
                'tenant_id' => $this->tenant->id,
                'menu_item_id' => $existingMenuItem->id,
                'quantity' => 1,
                'option_ids' => [],
            ]);

        $order = Order::factory()->completed()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $reorderMenuItem->id,
            'name' => $reorderMenuItem->name,
            'price' => $reorderMenuItem->price,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/orders/{$order->id}/reorder");

        $response->assertOk();

        $data = $response->json('data');
        $this->assertTrue($data['summary']['had_existing_cart_items']);
        // カートには既存の1アイテム + 再注文の1アイテム = 2アイテム
        $this->assertEquals(2, $data['cart']['item_count']);
    }

    // cart.item_count はカート行数ではなく数量合計を返す
    // （Cart モデルアクセサ・フロント (cartStore / "{item_count}点" 表示) と意味を一致させる）
    public function test_reorder_response_cart_item_count_returns_quantity_sum_not_row_count(): void
    {
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
            'available_days' => 127,
        ]);

        $order = Order::factory()->completed()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);

        // 1 行 × quantity 3。行数=1、数量合計=3 と分離可能なシナリオ。
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $menuItem->id,
            'name' => $menuItem->name,
            'price' => $menuItem->price,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/customer/orders/{$order->id}/reorder");

        $response->assertOk();

        $data = $response->json('data');
        $this->assertCount(1, $data['cart']['items'], 'カート行は 1 行のはず');
        $this->assertEquals(3, $data['cart']['item_count'], 'item_count は数量合計を返す必要がある');
    }

    // テスト用: 完了済み注文を利用可能なアイテム付きで作成する
    private function createCompletedOrderWithItems(array $orderAttributes = []): Order
    {
        $menuItem1 = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
            'available_days' => 127,
        ]);

        $menuItem2 = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 800,
            'is_active' => true,
            'is_sold_out' => false,
            'available_days' => 127,
        ]);

        $order = Order::factory()->completed()->create(array_merge([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ], $orderAttributes));

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $menuItem1->id,
            'name' => $menuItem1->name,
            'price' => $menuItem1->price,
            'quantity' => 1,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $menuItem2->id,
            'name' => $menuItem2->name,
            'price' => $menuItem2->price,
            'quantity' => 2,
        ]);

        return $order;
    }
}

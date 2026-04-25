<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Checkout;

use App\Enums\OrderStatus;
use App\Exceptions\OrderNumberGenerationException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemOption;
use App\Models\MenuItem;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Checkout\OrderCreationService;
use App\Services\OrderNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class OrderCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderNumberGenerator|MockInterface $mockOrderNumberGenerator;

    private OrderCreationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOrderNumberGenerator = Mockery::mock(OrderNumberGenerator::class);
        $this->service = new OrderCreationService($this->mockOrderNumberGenerator);
    }

    public function test_create_from_cart_creates_order_with_items(): void
    {

        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'テスト商品',
            'price' => 500,
        ]);
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
        ]);

        $businessDate = now()->startOfDay();
        $this->mockOrderNumberGenerator
            ->shouldReceive('getBusinessDate')
            ->once()
            ->andReturn($businessDate);
        $this->mockOrderNumberGenerator
            ->shouldReceive('generate')
            ->once()
            ->with($tenant->id, $businessDate)
            ->andReturn('A001');

        $order = $this->service->createFromCart($cart);

        $this->assertEquals('A001', $order->order_code);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals($tenant->id, $order->tenant_id);
        $this->assertEquals(OrderStatus::PendingPayment, $order->status);
        $this->assertEquals(1000, $order->total_amount);
        $this->assertEquals(1, $order->items->count());
        $this->assertEquals('テスト商品', $order->items->first()->name);
        $this->assertEquals(500, $order->items->first()->price);
        $this->assertEquals(2, $order->items->first()->quantity);
    }

    public function test_create_from_cart_includes_options(): void
    {

        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 500,
        ]);
        $optionGroup = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);
        $option = Option::factory()->create([
            'option_group_id' => $optionGroup->id,
            'name' => 'チーズ追加',
            'price' => 100,
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
        ]);
        CartItemOption::factory()->create([
            'cart_item_id' => $cartItem->id,
            'tenant_id' => $tenant->id,
            'option_id' => $option->id,
        ]);

        $businessDate = now()->startOfDay();
        $this->mockOrderNumberGenerator
            ->shouldReceive('getBusinessDate')
            ->once()
            ->andReturn($businessDate);
        $this->mockOrderNumberGenerator
            ->shouldReceive('generate')
            ->once()
            ->andReturn('A002');

        $order = $this->service->createFromCart($cart);

        $this->assertEquals(1200, $order->total_amount);
        $orderItem = $order->items->first();
        $this->assertEquals(1, $orderItem->options->count());
        $this->assertEquals('チーズ追加', $orderItem->options->first()->name);
        $this->assertEquals(100, $orderItem->options->first()->price);
    }

    public function test_snapshot_preserves_original_price_after_menu_item_update(): void
    {

        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => '元の商品名',
            'price' => 500,
        ]);
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
        ]);

        $businessDate = now()->startOfDay();
        $this->mockOrderNumberGenerator
            ->shouldReceive('getBusinessDate')
            ->once()
            ->andReturn($businessDate);
        $this->mockOrderNumberGenerator
            ->shouldReceive('generate')
            ->once()
            ->andReturn('A004');

        $order = $this->service->createFromCart($cart);

        $menuItem->update(['name' => '変更後の商品名', 'price' => 1000]);

        $order->refresh();
        $orderItem = $order->items->first();
        $this->assertEquals('元の商品名', $orderItem->name);
        $this->assertEquals(500, $orderItem->price);
    }

    public function test_calculate_total_amount_with_multiple_items(): void
    {

        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem1 = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 500,
        ]);
        $menuItem2 = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 800,
        ]);
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem1->id,
            'quantity' => 2,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem2->id,
            'quantity' => 1,
        ]);

        $businessDate = now()->startOfDay();
        $this->mockOrderNumberGenerator
            ->shouldReceive('getBusinessDate')
            ->once()
            ->andReturn($businessDate);
        $this->mockOrderNumberGenerator
            ->shouldReceive('generate')
            ->once()
            ->andReturn('A005');

        $order = $this->service->createFromCart($cart);

        $this->assertEquals(1800, $order->total_amount);
    }

    public function test_create_from_cart_retries_order_code_when_duplicate_on_insert(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 500,
        ]);
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
        ]);

        $businessDate = now()->startOfDay();
        Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'order_code' => 'A777',
            'business_date' => $businessDate,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 100,
        ]);

        $this->mockOrderNumberGenerator
            ->shouldReceive('getBusinessDate')
            ->once()
            ->andReturn($businessDate);
        $this->mockOrderNumberGenerator
            ->shouldReceive('generate')
            ->twice()
            ->with($tenant->id, $businessDate)
            ->andReturn('A777', 'A778');

        $order = $this->service->createFromCart($cart);

        $this->assertEquals('A778', $order->order_code);
        $this->assertDatabaseHas('orders', [
            'tenant_id' => $tenant->id,
            'business_date' => $businessDate->toDateString(),
            'order_code' => 'A778',
        ]);
    }

    // 複数 item × 複数 option を 1 注文に投入したとき、order_item_options への
    // INSERT が item 数や option 数に依存せず 1 クエリに集約されることを検証する
    public function test_create_from_cart_bulk_inserts_order_item_options_in_single_query(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();

        $menuItem1 = MenuItem::factory()->create(['tenant_id' => $tenant->id, 'price' => 500]);
        $menuItem2 = MenuItem::factory()->create(['tenant_id' => $tenant->id, 'price' => 800]);

        $optionGroup = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);
        $optionA = Option::factory()->create([
            'option_group_id' => $optionGroup->id,
            'name' => 'オプションA',
            'price' => 100,
        ]);
        $optionB = Option::factory()->create([
            'option_group_id' => $optionGroup->id,
            'name' => 'オプションB',
            'price' => 200,
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $cartItem1 = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem1->id,
            'quantity' => 1,
        ]);
        CartItemOption::factory()->create([
            'cart_item_id' => $cartItem1->id,
            'tenant_id' => $tenant->id,
            'option_id' => $optionA->id,
        ]);
        CartItemOption::factory()->create([
            'cart_item_id' => $cartItem1->id,
            'tenant_id' => $tenant->id,
            'option_id' => $optionB->id,
        ]);

        $cartItem2 = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem2->id,
            'quantity' => 1,
        ]);
        CartItemOption::factory()->create([
            'cart_item_id' => $cartItem2->id,
            'tenant_id' => $tenant->id,
            'option_id' => $optionA->id,
        ]);

        $businessDate = now()->startOfDay();
        $this->mockOrderNumberGenerator
            ->shouldReceive('getBusinessDate')
            ->once()
            ->andReturn($businessDate);
        $this->mockOrderNumberGenerator
            ->shouldReceive('generate')
            ->once()
            ->andReturn('B001');

        DB::enableQueryLog();
        $order = $this->service->createFromCart($cart);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $orderItemOptionInserts = collect($queries)
            ->filter(fn ($q) => str_contains(strtolower($q['query']), 'insert into')
                && str_contains($q['query'], 'order_item_options'))
            ->count();

        $this->assertSame(
            1,
            $orderItemOptionInserts,
            'order_item_options への INSERT は 1 クエリにまとめる必要があります（option 数に依存させない）'
        );

        $this->assertEquals(3, $order->items->sum(fn ($item) => $item->options->count()));
        $this->assertEquals(500 + 100 + 200 + 800 + 100, $order->total_amount);

        // tenant_id と option_id のスナップショット整合
        foreach ($order->items as $orderItem) {
            foreach ($orderItem->options as $orderItemOption) {
                $this->assertSame($tenant->id, $orderItemOption->tenant_id);
                $this->assertContains($orderItemOption->option_id, [$optionA->id, $optionB->id]);
            }
        }
    }

    public function test_create_from_cart_throws_when_order_code_duplicate_retries_exhausted(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 500,
        ]);
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
        ]);

        $businessDate = now()->startOfDay();
        Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'order_code' => 'A999',
            'business_date' => $businessDate,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 100,
        ]);

        $this->mockOrderNumberGenerator
            ->shouldReceive('getBusinessDate')
            ->once()
            ->andReturn($businessDate);
        $this->mockOrderNumberGenerator
            ->shouldReceive('generate')
            ->times(10)
            ->with($tenant->id, $businessDate)
            ->andReturn('A999');

        $this->expectException(OrderNumberGenerationException::class);

        $this->service->createFromCart($cart);
    }
}

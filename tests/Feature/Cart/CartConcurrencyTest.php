<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PaymentInitiationResult;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CartConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->customer()->create();
        $this->tenant = Tenant::factory()->create([
            'is_active' => true,
            'fincode_shop_id' => 'shop_test',
        ]);
        $this->setTenantAlwaysOpen($this->tenant);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_multiple_items_can_be_added_to_same_cart(): void
    {
        $item1 = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 300,
            'is_active' => true,
            'is_sold_out' => false,
        ]);
        $item2 = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        // 1つ目のアイテム追加
        $response1 = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/customer/cart/items', [
                'tenant_id' => $this->tenant->id,
                'menu_item_id' => $item1->id,
                'quantity' => 1,
            ]);
        $response1->assertCreated();

        // 2つ目のアイテム追加
        $response2 = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/customer/cart/items', [
                'tenant_id' => $this->tenant->id,
                'menu_item_id' => $item2->id,
                'quantity' => 2,
            ]);
        $response2->assertCreated();

        // カートに2つのアイテムが存在
        $cart = Cart::where('user_id', $this->customer->id)
            ->where('tenant_id', $this->tenant->id)
            ->first();
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart->items);
    }

    public function test_sold_out_item_cannot_be_added_to_cart(): void
    {
        $soldOutItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => true,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/customer/cart/items', [
                'tenant_id' => $this->tenant->id,
                'menu_item_id' => $soldOutItem->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(422);
    }

    public function test_inactive_item_cannot_be_added_to_cart(): void
    {
        $inactiveItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => false,
            'is_sold_out' => false,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/customer/cart/items', [
                'tenant_id' => $this->tenant->id,
                'menu_item_id' => $inactiveItem->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(422);
    }

    public function test_checkout_clears_cart_items(): void
    {
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
        ]);

        $mockPaymentService = Mockery::mock(PaymentService::class);
        $mockPaymentService->shouldReceive('initiate')
            ->once()
            ->andReturnUsing(function ($order, $method) {
                $payment = new Payment([
                    'order_id' => $order->id,
                    'tenant_id' => $order->tenant_id,
                    'provider' => 'fincode',
                    'method' => $method,
                ]);
                $payment->status = PaymentStatus::Pending;
                $payment->amount = $order->total_amount;
                $payment->fincode_id = 'fin_test';
                $payment->fincode_access_id = 'acc_test';
                $payment->save();

                return PaymentInitiationResult::forCard($payment, 'fin_test', 'acc_test');
            });
        $this->app->instance(PaymentService::class, $mockPaymentService);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => $cart->id,
                'payment_method' => 'card',
            ]);

        $response->assertCreated();

        // チェックアウト後、カート内のアイテムがクリアされている
        $this->assertCount(0, CartItem::where('cart_id', $cart->id)->get());
    }

    public function test_sold_out_during_checkout_is_rejected(): void
    {
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
        ]);

        // カート作成後に商品を売り切れにする（競合状態のシミュレーション）
        $menuItem->update(['is_sold_out' => true]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => $cart->id,
                'payment_method' => 'card',
            ]);

        $response->assertStatus(422);
        // 注文が作成されていないことを確認
        $this->assertDatabaseMissing('orders', [
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_item_deactivated_during_checkout_is_rejected(): void
    {
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
        ]);

        // カート作成後に商品を非アクティブにする
        $menuItem->update(['is_active' => false]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => $cart->id,
                'payment_method' => 'card',
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_modify_other_users_cart_item(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $otherCart = Cart::factory()->create([
            'user_id' => $otherCustomer->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $otherCartItem = CartItem::factory()->create([
            'cart_id' => $otherCart->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
        ]);

        // 他人のカートアイテムを更新しようとする
        $response = $this->actingAs($this->customer, 'sanctum')
            ->patchJson("/api/customer/cart/items/{$otherCartItem->id}", [
                'quantity' => 5,
            ]);

        $response->assertForbidden();
    }

    public function test_cannot_delete_other_users_cart_item(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'is_sold_out' => false,
        ]);

        $otherCart = Cart::factory()->create([
            'user_id' => $otherCustomer->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $otherCartItem = CartItem::factory()->create([
            'cart_id' => $otherCart->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->deleteJson("/api/customer/cart/items/{$otherCartItem->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('cart_items', ['id' => $otherCartItem->id]);
    }
}

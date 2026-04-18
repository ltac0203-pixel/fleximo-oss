<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PaymentInitiationResult;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
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

    private function createCartWithItem(int $price = 500, int $quantity = 1): Cart
    {
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => $price,
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
            'quantity' => $quantity,
        ]);

        return $cart;
    }

    private function mockPaymentServiceForCard(): void
    {
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
                $payment->save();

                $payment->fincode_id = 'fin_'.uniqid();
                $payment->fincode_access_id = 'acc_'.uniqid();
                $payment->save();

                return PaymentInitiationResult::forCard($payment, $payment->fincode_id, $payment->fincode_access_id);
            });

        $this->app->instance(PaymentService::class, $mockPaymentService);
    }

    private function mockPaymentServiceForPayPay(): void
    {
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
                $payment->save();

                $payment->fincode_id = 'fin_'.uniqid();
                $payment->save();

                return PaymentInitiationResult::forPayPay($payment, 'https://paypay.example.com/pay', 'sess_123');
            });

        $this->app->instance(PaymentService::class, $mockPaymentService);
    }

    public function test_checkout_with_card_payment_creates_order(): void
    {
        $cart = $this->createCartWithItem(500, 2);
        $this->mockPaymentServiceForCard();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => $cart->id,
                'payment_method' => 'card',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'order' => ['id', 'order_code', 'status', 'total_amount'],
                    'payment' => ['id', 'requires_token', 'requires_redirect'],
                ],
            ])
            ->assertJsonPath('data.payment.requires_token', true)
            ->assertJsonPath('data.payment.requires_redirect', false);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 1000,
        ]);
    }

    public function test_checkout_with_paypay_returns_redirect_url(): void
    {
        $cart = $this->createCartWithItem(800);
        $this->mockPaymentServiceForPayPay();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => $cart->id,
                'payment_method' => 'paypay',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.payment.requires_redirect', true)
            ->assertJsonPath('data.payment.requires_token', false);
    }

    public function test_checkout_rejects_empty_cart(): void
    {
        $cart = Cart::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => $cart->id,
                'payment_method' => 'card',
            ]);

        $response->assertStatus(422);
    }

    public function test_checkout_rejects_cart_with_sold_out_item(): void
    {
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => true,
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

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => $cart->id,
                'payment_method' => 'card',
            ]);

        $response->assertStatus(422);
    }

    public function test_checkout_rejects_invalid_payment_method(): void
    {
        $cart = $this->createCartWithItem();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => $cart->id,
                'payment_method' => 'bitcoin',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_checkout_requires_authentication(): void
    {
        $response = $this->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => 1,
                'payment_method' => 'card',
            ]);

        $response->assertUnauthorized();
    }

    public function test_checkout_rejects_non_customer_role(): void
    {
        $admin = User::factory()->tenantAdmin()->create();
        $cart = $this->createCartWithItem();

        $response = $this->actingAs($admin, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => $cart->id,
                'payment_method' => 'card',
            ]);

        $response->assertForbidden();
    }

    public function test_checkout_rejects_other_users_cart(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $cart = Cart::factory()->create([
            'user_id' => $otherCustomer->id,
            'tenant_id' => $this->tenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $this->tenant->id,
            'menu_item_id' => MenuItem::factory()->create([
                'tenant_id' => $this->tenant->id,
                'is_active' => true,
                'is_sold_out' => false,
            ])->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => $cart->id,
                'payment_method' => 'card',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['cart_id']);
    }

    public function test_checkout_requires_cart_id(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'payment_method' => 'card',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['cart_id']);
    }

    public function test_finalize_payment_rejects_other_users_payment(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $otherCustomer->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'status' => PaymentStatus::Pending,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'payment_id' => $payment->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_id']);
    }

    public function test_checkout_with_inactive_tenant_rejects(): void
    {
        $inactiveTenant = Tenant::factory()->inactive()->create([
            'fincode_shop_id' => 'shop_inactive',
        ]);
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $inactiveTenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
        ]);
        $cart = Cart::factory()->create([
            'user_id' => $this->customer->id,
            'tenant_id' => $inactiveTenant->id,
        ]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'tenant_id' => $inactiveTenant->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => $cart->id,
                'payment_method' => 'card',
            ]);

        // テナントが非アクティブの場合、決済方法が利用不可
        $response->assertStatus(422);
    }
}

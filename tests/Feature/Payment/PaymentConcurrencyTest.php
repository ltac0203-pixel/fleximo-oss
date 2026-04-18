<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CardPaymentService;
use App\Services\Checkout\CheckoutOrchestrator;
use App\Services\Fincode\FincodeClient;
use App\Services\PaymentInitiationResult;
use App\Services\PaymentService;
use App\Services\PayPayPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PaymentConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Tenant $tenant;

    private FincodeClient|MockInterface $mockFincodeClient;

    private CardPaymentService|MockInterface $mockCardService;

    private PayPayPaymentService|MockInterface $mockPayPayService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->customer()->create();
        $this->tenant = Tenant::factory()->create([
            'is_active' => true,
            'fincode_shop_id' => 'shop_concurrency_test',
        ]);
        $this->setTenantAlwaysOpen($this->tenant);

        $this->mockFincodeClient = Mockery::mock(FincodeClient::class);
        $this->mockCardService = Mockery::mock(CardPaymentService::class);
        $this->mockPayPayService = Mockery::mock(PayPayPaymentService::class);
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

    private function createPaymentService(): PaymentService
    {
        return new PaymentService(
            $this->mockFincodeClient,
            $this->mockCardService,
            $this->mockPayPayService,
        );
    }

    private function createOrderForPayment(int $amount = 500): Order
    {
        return Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->customer->id,
            'total_amount' => $amount,
            'status' => OrderStatus::PendingPayment,
        ]);
    }

    // -- 二重決済防止テスト --

    public function test_同一注文への二重決済開始は拒否される(): void
    {
        $order = $this->createOrderForPayment(1000);
        $service = $this->createPaymentService();

        // 1回目: カード決済を開始 → 成功
        $this->mockCardService
            ->shouldReceive('initiateCardPayment')
            ->once()
            ->andReturnUsing(fn ($payment) => PaymentInitiationResult::forCard(
                $payment,
                'p_first_'.uniqid(),
                'a_first_'.uniqid(),
            ));

        $result = $service->initiate($order, PaymentMethod::Card);

        $this->assertInstanceOf(PaymentInitiationResult::class, $result);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => PaymentStatus::Pending->value,
        ]);

        // 2回目: 同一注文にカード決済を再度開始 → 拒否
        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('既に処理中の決済が存在します');

        $service->initiate($order, PaymentMethod::Card);

        // Payment レコードは1件のみ
        $this->assertEquals(1, Payment::where('order_id', $order->id)->count());
    }

    public function test_異なる決済方法でも同一注文への二重決済は拒否される(): void
    {
        $order = $this->createOrderForPayment(1000);
        $service = $this->createPaymentService();

        // 1回目: カード決済を開始 → 成功
        $this->mockCardService
            ->shouldReceive('initiateCardPayment')
            ->once()
            ->andReturnUsing(fn ($payment) => PaymentInitiationResult::forCard(
                $payment,
                'p_card_'.uniqid(),
                'a_card_'.uniqid(),
            ));

        $service->initiate($order, PaymentMethod::Card);

        // 2回目: PayPay決済を開始 → 拒否（PayPayサービスは呼ばれない）
        $this->mockPayPayService
            ->shouldNotReceive('initiatePayPayPayment');

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('既に処理中の決済が存在します');

        $service->initiate($order, PaymentMethod::PayPay);
    }

    public function test_同一カートへの二重チェックアウトは2回目が拒否される(): void
    {
        $cart = $this->createCartWithItem(500, 2);

        // PaymentService をモックして外部API呼び出しを回避
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

        // 1回目: チェックアウト → 201 Created
        $response1 = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => $cart->id,
                'payment_method' => 'card',
            ]);

        $response1->assertCreated();

        // 2回目: 同一カートで再チェックアウト → 422（カートが空になっているため）
        $response2 = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/checkout', [
                'cart_id' => $cart->id,
                'payment_method' => 'card',
            ]);

        $response2->assertStatus(422);

        // Order は1件のみ
        $this->assertEquals(
            1,
            Order::where('user_id', $this->customer->id)
                ->where('tenant_id', $this->tenant->id)
                ->count()
        );
    }

    // -- finalize / executePaymentFor3ds 冪等性テスト --

    public function test_webhook完了後のfinalize呼び出しは冪等に成功する(): void
    {
        $order = $this->createOrderForPayment(1000);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'status' => PaymentStatus::Completed,
            'amount' => 1000,
        ]);

        $order->payment_id = $payment->id;
        $order->status = OrderStatus::Paid;
        $order->save();

        /** @var CheckoutOrchestrator $orchestrator */
        $orchestrator = app(CheckoutOrchestrator::class);

        // Webhook で既に完了済み → finalizePayment は例外なく Order を返す（早期リターン）
        $result = $orchestrator->finalizePayment($payment);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals($order->id, $result->id);
        $this->assertEquals(OrderStatus::Paid, $result->status);
    }

    public function test_失敗済み決済へのfinalize呼び出しはエラーを返す(): void
    {
        $order = $this->createOrderForPayment(1000);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'status' => PaymentStatus::Failed,
            'amount' => 1000,
        ]);

        $order->payment_id = $payment->id;
        $order->status = OrderStatus::PaymentFailed;
        $order->save();

        /** @var CheckoutOrchestrator $orchestrator */
        $orchestrator = app(CheckoutOrchestrator::class);

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('この決済は失敗しています。');

        $orchestrator->finalizePayment($payment);
    }

    public function test_完了済み決済への3ds実行は拒否される(): void
    {
        $order = $this->createOrderForPayment(1000);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'status' => PaymentStatus::Completed,
            'amount' => 1000,
        ]);

        /** @var CheckoutOrchestrator $orchestrator */
        $orchestrator = app(CheckoutOrchestrator::class);

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('この決済は既に処理済みです。');

        $orchestrator->executePaymentFor3ds($payment, 'tok_test_123');
    }
}

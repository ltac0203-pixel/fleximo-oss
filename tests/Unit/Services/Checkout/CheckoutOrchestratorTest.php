<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\EmptyCartException;
use App\Exceptions\ItemNotAvailableException;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\PaymentMethodNotAvailableException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemOption;
use App\Models\MenuItem;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CartService;
use App\Services\Checkout\CheckoutOrchestrator;
use App\Services\Checkout\CheckoutValidationService;
use App\Services\Checkout\OrderCreationService;
use App\Services\CheckoutResult;
use App\Services\OrderNumberGenerator;
use App\Services\PaymentInitiationResult;
use App\Services\PaymentService;
use App\Services\PayPayPaymentService;
use App\Services\ThreeDsAuthResult;
use App\Services\ThreeDsPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CheckoutOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService|MockInterface $mockPaymentService;

    private ThreeDsPaymentService|MockInterface $mockThreeDsPaymentService;

    private PayPayPaymentService|MockInterface $mockPayPayPaymentService;

    private OrderNumberGenerator|MockInterface $mockOrderNumberGenerator;

    private CartService|MockInterface $mockCartService;

    private CheckoutOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockPaymentService = Mockery::mock(PaymentService::class);
        $this->mockThreeDsPaymentService = Mockery::mock(ThreeDsPaymentService::class);
        $this->mockPayPayPaymentService = Mockery::mock(PayPayPaymentService::class);
        $this->mockOrderNumberGenerator = Mockery::mock(OrderNumberGenerator::class);
        $this->mockCartService = Mockery::mock(CartService::class)->shouldIgnoreMissing();

        $validationService = new CheckoutValidationService;
        $orderCreationService = new OrderCreationService($this->mockOrderNumberGenerator);

        $this->orchestrator = new CheckoutOrchestrator(
            $validationService,
            $orderCreationService,
            $this->mockPaymentService,
            $this->mockThreeDsPaymentService,
            $this->mockPayPayPaymentService,
            $this->mockCartService
        );
    }

    public function test_process_checkout_success_with_card_payment(): void
    {

        $tenant = Tenant::factory()->create(['is_active' => true]);
        $this->setTenantAlwaysOpen($tenant);
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
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

        $this->mockPaymentService
            ->shouldReceive('initiate')
            ->once()
            ->andReturnUsing(function ($order, $method) use ($tenant) {
                $payment = new Payment([
                    'order_id' => $order->id,
                    'tenant_id' => $tenant->id,
                    'provider' => 'fincode',
                    'method' => $method,
                ]);
                $payment->status = PaymentStatus::Pending;
                $payment->amount = $order->total_amount;
                $payment->save();

                return PaymentInitiationResult::forCard($payment, 'fincode_123', 'access_456');
            });
        $this->mockCartService
            ->shouldReceive('clearItems')
            ->once()
            ->with(Mockery::on(fn ($targetCart) => $targetCart->id === $cart->id));

        $result = $this->orchestrator->processCheckout($cart, PaymentMethod::Card);

        $this->assertInstanceOf(CheckoutResult::class, $result);
        $this->assertEquals('A001', $result->order->order_code);
        $this->assertEquals(OrderStatus::PendingPayment, $result->order->status);
        $this->assertEquals(1000, $result->order->total_amount);
        $this->assertTrue($result->requiresToken);
        $this->assertFalse($result->requiresRedirect);
    }

    public function test_process_checkout_success_with_paypay_payment(): void
    {

        $tenant = Tenant::factory()->create(['is_active' => true]);
        $this->setTenantAlwaysOpen($tenant);
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 800,
            'is_active' => true,
            'is_sold_out' => false,
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
            ->andReturn('A002');

        $this->mockPaymentService
            ->shouldReceive('initiate')
            ->once()
            ->andReturnUsing(function ($order, $method) use ($tenant) {
                $payment = new Payment([
                    'order_id' => $order->id,
                    'tenant_id' => $tenant->id,
                    'provider' => 'fincode',
                    'method' => $method,
                ]);
                $payment->status = PaymentStatus::Pending;
                $payment->amount = $order->total_amount;
                $payment->save();

                return PaymentInitiationResult::forPayPay($payment, 'https://paypay.example.com/checkout', 'sess_123');
            });
        $this->mockCartService
            ->shouldReceive('clearItems')
            ->once()
            ->with(Mockery::on(fn ($targetCart) => $targetCart->id === $cart->id));

        $result = $this->orchestrator->processCheckout($cart, PaymentMethod::PayPay);

        $this->assertInstanceOf(CheckoutResult::class, $result);
        $this->assertEquals(800, $result->order->total_amount);
        $this->assertTrue($result->requiresRedirect);
        $this->assertFalse($result->requiresToken);
        $this->assertEquals('https://paypay.example.com/checkout', $result->redirectUrl);
    }

    public function test_process_checkout_with_options(): void
    {

        $tenant = Tenant::factory()->create(['is_active' => true]);
        $this->setTenantAlwaysOpen($tenant);
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
        ]);
        $optionGroup = OptionGroup::factory()->create(['tenant_id' => $tenant->id]);
        $option = Option::factory()->create([
            'option_group_id' => $optionGroup->id,
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
            ->andReturn('A003');

        $this->mockPaymentService
            ->shouldReceive('initiate')
            ->once()
            ->andReturnUsing(function ($order, $method) use ($tenant) {
                $payment = new Payment([
                    'order_id' => $order->id,
                    'tenant_id' => $tenant->id,
                    'provider' => 'fincode',
                    'method' => $method,
                ]);
                $payment->status = PaymentStatus::Pending;
                $payment->amount = $order->total_amount;
                $payment->save();

                return PaymentInitiationResult::forCard($payment, 'fincode_123', 'access_456');
            });
        $this->mockCartService
            ->shouldReceive('clearItems')
            ->once()
            ->with(Mockery::on(fn ($targetCart) => $targetCart->id === $cart->id));

        $result = $this->orchestrator->processCheckout($cart, PaymentMethod::Card);

        $this->assertEquals(1200, $result->order->total_amount);

        $orderItem = $result->order->items->first();
        $this->assertEquals(1, $orderItem->options->count());
        $this->assertEquals($option->name, $orderItem->options->first()->name);
        $this->assertEquals($option->price, $orderItem->options->first()->price);
    }

    public function test_process_checkout_fails_with_empty_cart(): void
    {

        $tenant = Tenant::factory()->create(['is_active' => true]);
        $user = User::factory()->customer()->create();
        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->expectException(EmptyCartException::class);
        $this->orchestrator->processCheckout($cart, PaymentMethod::Card);
    }

    public function test_process_checkout_fails_with_unavailable_item(): void
    {

        $tenant = Tenant::factory()->create(['is_active' => true]);
        $this->setTenantAlwaysOpen($tenant);
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 500,
            'is_active' => false,
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

        $this->expectException(ItemNotAvailableException::class);
        $this->orchestrator->processCheckout($cart, PaymentMethod::Card);
    }

    public function test_process_checkout_fails_with_sold_out_item(): void
    {

        $tenant = Tenant::factory()->create(['is_active' => true]);
        $this->setTenantAlwaysOpen($tenant);
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => true,
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

        $this->expectException(ItemNotAvailableException::class);
        $this->orchestrator->processCheckout($cart, PaymentMethod::Card);
    }

    public function test_process_checkout_fails_with_inactive_tenant(): void
    {

        $tenant = Tenant::factory()->create(['is_active' => false]);
        $this->setTenantAlwaysOpen($tenant);
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
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

        $this->expectException(PaymentMethodNotAvailableException::class);
        $this->orchestrator->processCheckout($cart, PaymentMethod::Card);
    }

    public function test_finalize_payment_success(): void
    {

        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'status' => PaymentStatus::Pending,
        ]);

        $this->mockPayPayPaymentService
            ->shouldReceive('confirm')
            ->once()
            ->with(Mockery::on(fn ($p) => $p->id === $payment->id))
            ->andReturnUsing(function ($payment) {
                $payment->update(['status' => PaymentStatus::Completed]);
                $payment->order->markAsPaid();

                return true;
            });

        $result = $this->orchestrator->finalizePayment($payment);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals($order->id, $result->id);
    }

    public function test_finalize_payment_returns_order_when_already_completed(): void
    {

        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::Paid,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'status' => PaymentStatus::Completed,
        ]);

        $result = $this->orchestrator->finalizePayment($payment);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals($order->id, $result->id);
    }

    public function test_finalize_payment_fails_when_already_failed(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PaymentFailed,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'status' => PaymentStatus::Failed,
        ]);

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('この決済は失敗しています。');
        $this->orchestrator->finalizePayment($payment);
    }

    public function test_process_3ds_callback_promotes_pending_payment_before_challenge_confirmation(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
        ]);

        $this->mockThreeDsPaymentService
            ->shouldReceive('confirmAndExecute')
            ->once()
            ->with(
                Mockery::on(fn ($targetPayment) => $targetPayment->id === $payment->id && $targetPayment->status === PaymentStatus::Processing),
                'challenge_param'
            )
            ->andReturn(ThreeDsAuthResult::authenticated($payment));

        $result = $this->orchestrator->process3dsCallback($payment, 'challenge_param', 'AuthResultReady');

        $payment->refresh();
        $this->assertTrue($result->isAuthenticated());
        $this->assertEquals(PaymentStatus::Processing, $payment->status);
    }

    public function test_process_3ds_callback_executes_authentication_after_method_completion(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Processing,
        ]);

        $this->mockThreeDsPaymentService
            ->shouldReceive('executeAuthentication')
            ->once()
            ->with(
                Mockery::on(fn ($targetPayment) => $targetPayment->id === $payment->id && $targetPayment->status === PaymentStatus::Processing),
                'method_param'
            )
            ->andReturn(ThreeDsAuthResult::requiresChallenge($payment, 'https://acs.example.com/challenge'));

        $result = $this->orchestrator->process3dsCallback($payment, 'method_param');

        $this->assertTrue($result->requiresRedirect());
        $this->assertSame('https://acs.example.com/challenge', $result->challengeUrl);
    }

    public function test_process_3ds_callback_returns_authenticated_for_completed_payment(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::Paid,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Completed,
        ]);

        $result = $this->orchestrator->process3dsCallback($payment, 'callback_param', 'AuthResultReady');

        $this->assertTrue($result->isAuthenticated());
    }

    public function test_process_3ds_callback_rejects_failed_payment(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Failed,
        ]);

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('この決済は3DS認証待ちではありません。');

        $this->orchestrator->process3dsCallback($payment, 'callback_param', 'AuthResultReady');
    }

    public function test_snapshot_preserves_item_data(): void
    {

        $tenant = Tenant::factory()->create(['is_active' => true]);
        $this->setTenantAlwaysOpen($tenant);
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'テスト商品',
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
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

        $this->mockPaymentService
            ->shouldReceive('initiate')
            ->once()
            ->andReturnUsing(function ($order, $method) use ($tenant) {
                $payment = new Payment([
                    'order_id' => $order->id,
                    'tenant_id' => $tenant->id,
                    'provider' => 'fincode',
                    'method' => $method,
                ]);
                $payment->status = PaymentStatus::Pending;
                $payment->amount = $order->total_amount;
                $payment->save();

                return PaymentInitiationResult::forCard($payment, 'fincode_123', 'access_456');
            });
        $this->mockCartService
            ->shouldReceive('clearItems')
            ->once()
            ->with(Mockery::on(fn ($targetCart) => $targetCart->id === $cart->id));

        $result = $this->orchestrator->processCheckout($cart, PaymentMethod::Card);

        $menuItem->update(['price' => 1000, 'name' => '変更後の名前']);

        $orderItem = $result->order->fresh()->items->first();
        $this->assertEquals('テスト商品', $orderItem->name);
        $this->assertEquals(500, $orderItem->price);
    }

    public function test_process_checkout_does_not_clear_cart_when_payment_initiation_fails(): void
    {
        $tenant = Tenant::factory()->create(['is_active' => true]);
        $this->setTenantAlwaysOpen($tenant);
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
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
            ->andReturn('A005');

        $this->mockPaymentService
            ->shouldReceive('initiate')
            ->once()
            ->andThrow(new PaymentFailedException(null, 'E01100001', '決済の開始に失敗しました。'));
        $this->mockCartService
            ->shouldNotReceive('clearItems');

        $this->expectException(PaymentFailedException::class);
        $this->orchestrator->processCheckout($cart, PaymentMethod::Card);
    }

    public function test_process_checkout_returns_success_when_cart_clear_fails_after_payment_initiation(): void
    {
        $tenant = Tenant::factory()->create(['is_active' => true]);
        $this->setTenantAlwaysOpen($tenant);
        $user = User::factory()->customer()->create();
        $menuItem = MenuItem::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 500,
            'is_active' => true,
            'is_sold_out' => false,
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
            ->andReturn('A006');

        $this->mockPaymentService
            ->shouldReceive('initiate')
            ->once()
            ->andReturnUsing(function ($order, $method) use ($tenant) {
                $payment = new Payment([
                    'order_id' => $order->id,
                    'tenant_id' => $tenant->id,
                    'provider' => 'fincode',
                    'method' => $method,
                ]);
                $payment->status = PaymentStatus::Pending;
                $payment->amount = $order->total_amount;
                $payment->save();

                return PaymentInitiationResult::forCard($payment, 'fincode_123', 'access_456');
            });

        $this->mockCartService
            ->shouldReceive('clearItems')
            ->twice()
            ->with(Mockery::on(fn ($targetCart) => $targetCart->id === $cart->id))
            ->andThrow(new \RuntimeException('cart clear failed'));

        $result = $this->orchestrator->processCheckout($cart, PaymentMethod::Card);

        $this->assertInstanceOf(CheckoutResult::class, $result);
        $this->assertEquals('A006', $result->order->order_code);
        $this->assertTrue($result->requiresToken);
        $this->assertFalse($result->requiresRedirect);
        $this->assertTrue($result->cartClearFailed);
    }
}

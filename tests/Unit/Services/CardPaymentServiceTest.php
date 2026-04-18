<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentFailedException;
use App\Models\FincodeCard;
use App\Models\FincodeCustomer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CardPaymentService;
use App\Services\Fincode\FincodeApiException;
use App\Services\Fincode\FincodeClient;
use App\Services\Fincode\FincodePaymentResponse;
use App\Services\FincodeCustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CardPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private FincodeClient|MockInterface $mockClient;

    private FincodeCustomerService|MockInterface $mockCustomerService;

    private CardPaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(FincodeClient::class);
        $this->mockCustomerService = Mockery::mock(FincodeCustomerService::class);
        $this->service = new CardPaymentService($this->mockClient, $this->mockCustomerService);
    }

    public function test_initiates_card_payment(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_test_tenant_123',
        ]);
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::Card,
        ]);
        $expectedCallbackPath = "/order/checkout/callback/3ds/{$payment->id}";

        // 新規カード（cardId=null）のため ensureCustomerExists が呼ばれる
        $fincodeCustomer = FincodeCustomer::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'cus_test_123',
        ]);
        $this->mockCustomerService
            ->shouldReceive('ensureCustomerExists')
            ->once()
            ->with(Mockery::on(fn ($u) => $u->id === $user->id), Mockery::on(fn ($t) => $t->id === $tenant->id))
            ->andReturn($fincodeCustomer);

        $this->mockClient
            ->shouldReceive('createCardPaymentWith3ds')
            ->once()
            ->with(Mockery::on(function ($params) use ($order, $payment, $expectedCallbackPath) {
                return $params['amount'] === 1000
                    && $params['order_id'] === $order->id
                    && $params['payment_id'] === $payment->id
                    && $params['tenant_shop_id'] === 's_test_tenant_123'
                    && isset($params['tds2_ret_url'])
                    && str_contains((string) $params['tds2_ret_url'], $expectedCallbackPath);
            }))
            ->andReturn(new FincodePaymentResponse(
                id: 'p_123456',
                accessId: 'a_789012',
                status: 'AWAITING_CUSTOMER_PAYMENT',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: (string) $order->id,
                errorCode: null,
                rawResponse: [],
            ));

        $result = $this->service->initiateCardPayment($payment, $order);

        $this->assertFalse($result->requiresRedirect);
        $this->assertTrue($result->requiresToken);
        $this->assertEquals('p_123456', $result->fincodeId);
        $this->assertEquals('a_789012', $result->accessId);

        $payment->refresh();
        $this->assertEquals('p_123456', $payment->fincode_id);
        $this->assertEquals('cus_test_123', $payment->fincode_customer_id);
        $this->assertNull($payment->fincode_card_id);
    }

    public function test_initiate_card_payment_fails_when_saved_card_belongs_to_different_tenant(): void
    {
        $tenantA = Tenant::factory()->create([
            'fincode_shop_id' => 's_tenant_a',
        ]);
        $tenantB = Tenant::factory()->create([
            'fincode_shop_id' => 's_tenant_b',
        ]);
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'tenant_id' => $tenantA->id,
            'user_id' => $user->id,
            'total_amount' => 1200,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenantA->id,
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::Card,
        ]);

        $fincodeCustomer = FincodeCustomer::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenantB->id,
            'fincode_customer_id' => 'cus_other_tenant',
        ]);
        $card = FincodeCard::factory()->create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'card_other_tenant',
        ]);

        $this->mockClient
            ->shouldNotReceive('createCardPaymentWith3ds');

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('このカードは当店舗で使用できません。');

        try {
            $this->service->initiateCardPayment($payment, $order, $card->id);
        } finally {
            $payment->refresh();
            $order->refresh();

            $this->assertEquals(PaymentStatus::Failed, $payment->status);
            $this->assertEquals(OrderStatus::PaymentFailed, $order->status);
        }
    }

    public function test_initiate_card_payment_fails_when_saved_card_belongs_to_different_user(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_tenant_user_check',
        ]);
        $orderUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $orderUser->id,
            'total_amount' => 1200,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::Card,
        ]);

        $fincodeCustomer = FincodeCustomer::factory()->create([
            'user_id' => $otherUser->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'cus_other_user',
        ]);
        $card = FincodeCard::factory()->create([
            'fincode_customer_id' => $fincodeCustomer->id,
            'fincode_card_id' => 'card_other_user',
        ]);

        $this->mockClient
            ->shouldNotReceive('createCardPaymentWith3ds');

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('このカードは当店舗で使用できません。');

        try {
            $this->service->initiateCardPayment($payment, $order, $card->id);
        } finally {
            $payment->refresh();
            $order->refresh();

            $this->assertEquals(PaymentStatus::Failed, $payment->status);
            $this->assertEquals(OrderStatus::PaymentFailed, $order->status);
        }
    }

    public function test_executes_card_payment_successfully(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_exec_tenant',
        ]);
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'fincode_id' => 'p_123456',
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::Card,
        ]);

        $this->mockClient
            ->shouldReceive('getPayment')
            ->with('p_123456', 's_exec_tenant', 'Card')
            ->andReturn(new FincodePaymentResponse(
                id: 'p_123456',
                accessId: 'a_789012',
                status: 'UNPROCESSED',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
            ));

        $this->mockClient
            ->shouldReceive('executeCardPayment')
            ->once()
            ->with('p_123456', Mockery::on(function ($params) {
                return $params['token'] === 'tok_123'
                    && $params['tenant_shop_id'] === 's_exec_tenant';
            }))
            ->andReturn(new FincodePaymentResponse(
                id: 'p_123456',
                accessId: 'a_789012',
                status: 'CAPTURED',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
            ));

        $result = $this->service->executeCardPayment($payment, 'tok_123');

        $this->assertEquals(PaymentStatus::Completed, $result->status);

        $order->refresh();
        $this->assertEquals(OrderStatus::Paid, $order->status);
    }

    public function test_executes_card_payment_failure(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_fail_tenant',
        ]);
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'fincode_id' => 'p_123456',
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::Card,
        ]);

        $this->mockClient
            ->shouldReceive('getPayment')
            ->with('p_123456', 's_fail_tenant', 'Card')
            ->andReturn(new FincodePaymentResponse(
                id: 'p_123456',
                accessId: 'a_789012',
                status: 'UNPROCESSED',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
            ));

        $this->mockClient
            ->shouldReceive('executeCardPayment')
            ->once()
            ->andThrow(new FincodeApiException('E01100201', [], 'Card declined'));

        $this->expectException(PaymentFailedException::class);

        $this->service->executeCardPayment($payment, 'tok_invalid');
    }

    public function test_execute_card_payment_throws_when_access_id_cannot_be_retrieved(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_missing_access',
        ]);
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'fincode_id' => 'p_123456',
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::Card,
        ]);

        $this->mockClient
            ->shouldReceive('getPayment')
            ->with('p_123456', 's_missing_access', 'Card')
            ->andReturn(new FincodePaymentResponse(
                id: 'p_123456',
                accessId: null,
                status: 'UNPROCESSED',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
            ));

        $this->mockClient
            ->shouldReceive('executeCardPayment')
            ->never();

        try {
            $this->service->executeCardPayment($payment, 'tok_123');
            $this->fail('PaymentFailedException was not thrown.');
        } catch (PaymentFailedException $e) {
            $this->assertSame('fincode access_idが取得できませんでした。', $e->getMessage());
        }

        $payment->refresh();
        $order->refresh();
        $this->assertEquals(PaymentStatus::Pending, $payment->status);
        $this->assertEquals(OrderStatus::PendingPayment, $order->status);
    }

    public function test_initiates_card_payment_with_null_fincode_shop_id(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => null,
        ]);
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::Card,
        ]);
        $expectedCallbackPath = "/order/checkout/callback/3ds/{$payment->id}";

        // 新規カード（cardId=null）のため ensureCustomerExists が呼ばれる
        $fincodeCustomer = FincodeCustomer::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'cus_null_tenant',
        ]);
        $this->mockCustomerService
            ->shouldReceive('ensureCustomerExists')
            ->once()
            ->andReturn($fincodeCustomer);

        $this->mockClient
            ->shouldReceive('createCardPaymentWith3ds')
            ->once()
            ->with(Mockery::on(function ($params) use ($order, $payment, $expectedCallbackPath) {
                return $params['amount'] === 1000
                    && $params['order_id'] === $order->id
                    && $params['payment_id'] === $payment->id
                    && $params['tenant_shop_id'] === null
                    && isset($params['tds2_ret_url'])
                    && str_contains((string) $params['tds2_ret_url'], $expectedCallbackPath);
            }))
            ->andReturn(new FincodePaymentResponse(
                id: 'p_null_tenant',
                accessId: 'a_null_tenant',
                status: 'AWAITING_CUSTOMER_PAYMENT',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: (string) $order->id,
                errorCode: null,
                rawResponse: [],
            ));

        $result = $this->service->initiateCardPayment($payment, $order);

        $this->assertEquals('p_null_tenant', $result->fincodeId);

        $payment->refresh();
        $this->assertEquals('p_null_tenant', $payment->fincode_id);
    }

    public function test_initiates_card_payment_sets_customer_id_for_new_card(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_cust_tenant',
        ]);
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'total_amount' => 2000,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::Card,
        ]);

        $fincodeCustomer = FincodeCustomer::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'fincode_customer_id' => 'cus_checkout_789',
        ]);
        $this->mockCustomerService
            ->shouldReceive('ensureCustomerExists')
            ->once()
            ->with(Mockery::on(fn ($u) => $u->id === $user->id), Mockery::on(fn ($t) => $t->id === $tenant->id))
            ->andReturn($fincodeCustomer);

        $this->mockClient
            ->shouldReceive('createCardPaymentWith3ds')
            ->once()
            ->with(Mockery::on(function ($params) use ($payment) {
                return $params['payment_id'] === $payment->id;
            }))
            ->andReturn(new FincodePaymentResponse(
                id: 'p_cust_test',
                accessId: 'a_cust_test',
                status: 'AWAITING_CUSTOMER_PAYMENT',
                amount: 2000,
                payType: 'Card',
                linkUrl: null,
                clientField1: (string) $order->id,
                errorCode: null,
                rawResponse: [],
            ));

        $result = $this->service->initiateCardPayment($payment, $order);

        $payment->refresh();
        // 新規カードでも customer_id が設定される
        $this->assertEquals('cus_checkout_789', $payment->fincode_customer_id);
        // card_id は null（新規カードのため）
        $this->assertNull($payment->fincode_card_id);
        // usesSavedCard() は false を返す（card_id が null のため）
        $this->assertFalse($payment->usesSavedCard());
    }
}

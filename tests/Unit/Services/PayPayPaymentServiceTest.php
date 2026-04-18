<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Fincode\FincodeClient;
use App\Services\Fincode\FincodePaymentResponse;
use App\Services\PayPayPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PayPayPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private FincodeClient|MockInterface $mockClient;

    private PayPayPaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(FincodeClient::class);
        $this->service = new PayPayPaymentService($this->mockClient);
    }

    public function test_initiates_paypay_payment(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_test_tenant_paypay',
        ]);
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'total_amount' => 1500,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::PayPay,
        ]);

        $this->mockClient
            ->shouldReceive('createPayPayPayment')
            ->once()
            ->with(Mockery::on(function ($params) use ($payment) {
                return $params['tenant_shop_id'] === 's_test_tenant_paypay'
                    && $params['payment_id'] === $payment->id;
            }))
            ->andReturn(new FincodePaymentResponse(
                id: 'pay_123456',
                accessId: 'acc_789',
                status: 'UNPROCESSED',
                amount: 1500,
                payType: 'Paypay',
                linkUrl: null,
                clientField1: (string) $order->id,
                errorCode: null,
                rawResponse: [],
            ));

        $this->mockClient
            ->shouldReceive('executePayPayPayment')
            ->once()
            ->with('pay_123456', Mockery::on(function ($params) {
                return $params['access_id'] === 'acc_789'
                    && $params['tenant_shop_id'] === 's_test_tenant_paypay';
            }))
            ->andReturn(new FincodePaymentResponse(
                id: 'pay_123456',
                accessId: 'acc_789',
                status: 'AWAITING_CUSTOMER_PAYMENT',
                amount: 1500,
                payType: 'Paypay',
                linkUrl: 'https://paypay.example.com/checkout',
                clientField1: (string) $order->id,
                errorCode: null,
                rawResponse: [],
            ));

        $result = $this->service->initiatePayPayPayment($payment, $order, [
            'redirect_url' => 'https://example.com/callback',
        ]);

        $this->assertTrue($result->requiresRedirect);
        $this->assertFalse($result->requiresToken);
        $this->assertEquals('https://paypay.example.com/checkout', $result->redirectUrl);

        $payment->refresh();
        $this->assertEquals('pay_123456', $payment->fincode_id);
        $this->assertEquals(PaymentStatus::Processing, $payment->status);
    }

    public function test_confirms_payment_successfully(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_confirm_tenant',
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
            'status' => PaymentStatus::Processing,
            'method' => PaymentMethod::PayPay,
        ]);

        $this->mockClient
            ->shouldReceive('getPayment')
            ->once()
            ->with('p_123456', 's_confirm_tenant', 'Paypay')
            ->andReturn(new FincodePaymentResponse(
                id: 'p_123456',
                accessId: null,
                status: 'CAPTURED',
                amount: 1500,
                payType: 'Paypay',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
            ));

        $result = $this->service->confirm($payment);

        $this->assertTrue($result);

        $payment->refresh();
        $order->refresh();

        $this->assertEquals(PaymentStatus::Completed, $payment->status);
        $this->assertEquals(OrderStatus::Paid, $order->status);
    }

    public function test_confirms_payment_returns_false_when_still_processing(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_processing_tenant',
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
            'status' => PaymentStatus::Processing,
            'method' => PaymentMethod::PayPay,
        ]);

        $this->mockClient
            ->shouldReceive('getPayment')
            ->once()
            ->with('p_123456', 's_processing_tenant', 'Paypay')
            ->andReturn(new FincodePaymentResponse(
                id: 'p_123456',
                accessId: null,
                status: 'AWAITING_AUTHENTICATION',
                amount: 1500,
                payType: 'Paypay',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
            ));

        $result = $this->service->confirm($payment);

        $this->assertFalse($result);

        $payment->refresh();
        $this->assertEquals(PaymentStatus::Processing, $payment->status);
    }

    public function test_confirm_returns_true_when_already_completed(): void
    {
        $tenant = Tenant::factory()->create();
        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'fincode_id' => 'p_123456',
            'status' => PaymentStatus::Completed,
        ]);

        $this->mockClient->shouldNotReceive('getPayment');

        $result = $this->service->confirm($payment);

        $this->assertTrue($result);
    }
}

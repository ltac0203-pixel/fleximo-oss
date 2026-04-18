<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentFailedException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CardPaymentService;
use App\Services\Fincode\FincodeApiException;
use App\Services\Fincode\FincodeClient;
use App\Services\Fincode\FincodePaymentResponse;
use App\Services\PaymentInitiationResult;
use App\Services\PaymentService;
use App\Services\PayPayPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private FincodeClient|MockInterface $mockClient;

    private CardPaymentService|MockInterface $mockCardService;

    private PayPayPaymentService|MockInterface $mockPayPayService;

    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(FincodeClient::class);
        $this->mockCardService = Mockery::mock(CardPaymentService::class);
        $this->mockPayPayService = Mockery::mock(PayPayPaymentService::class);
        $this->service = new PaymentService(
            $this->mockClient,
            $this->mockCardService,
            $this->mockPayPayService
        );
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

        $this->mockCardService
            ->shouldReceive('initiateCardPayment')
            ->once()
            ->andReturnUsing(function ($payment) {
                return PaymentInitiationResult::forCard(
                    $payment,
                    'p_123456',
                    'a_789012'
                );
            });

        $result = $this->service->initiate($order, PaymentMethod::Card);

        $this->assertFalse($result->requiresRedirect);
        $this->assertTrue($result->requiresToken);
        $this->assertEquals('p_123456', $result->fincodeId);
        $this->assertEquals('a_789012', $result->accessId);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => PaymentMethod::Card->value,
            'status' => PaymentStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_id' => $result->payment->id,
        ]);
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

        $this->mockPayPayService
            ->shouldReceive('initiatePayPayPayment')
            ->once()
            ->andReturnUsing(function ($payment) {
                return PaymentInitiationResult::forPayPay(
                    $payment,
                    'https://paypay.example.com/checkout',
                    's_123456'
                );
            });

        $result = $this->service->initiate($order, PaymentMethod::PayPay, [
            'success_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
        ]);

        $this->assertTrue($result->requiresRedirect);
        $this->assertFalse($result->requiresToken);
        $this->assertEquals('https://paypay.example.com/checkout', $result->redirectUrl);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => PaymentMethod::PayPay->value,
            'status' => PaymentStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_id' => $result->payment->id,
        ]);
    }

    public function test_throws_exception_when_existing_payment_exists(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'status' => PaymentStatus::Processing,
        ]);

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('既に処理中の決済が存在します');

        $this->service->initiate($order, PaymentMethod::Card);
    }

    public function test_checks_payment_status(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_status_tenant',
        ]);
        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'fincode_id' => 'p_123456',
            'status' => PaymentStatus::Processing,
        ]);

        $this->mockClient
            ->shouldReceive('getPayment')
            ->once()
            ->with('p_123456', 's_status_tenant', 'Card')
            ->andReturn(new FincodePaymentResponse(
                id: 'p_123456',
                accessId: null,
                status: 'CAPTURED',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
            ));

        $status = $this->service->checkStatus($payment);

        $this->assertEquals(PaymentStatus::Completed, $status);
    }

    public function test_marks_payment_and_order_failed_when_fincode_api_fails(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_failed_tenant',
        ]);
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::PendingPayment,
        ]);

        $this->mockCardService
            ->shouldReceive('initiateCardPayment')
            ->once()
            ->andThrow(new FincodeApiException(
                errorCode: 'E01100001',
                response: ['error' => 'temporary'],
                message: 'fincode temporary error'
            ));

        $this->expectException(PaymentFailedException::class);

        try {
            $this->service->initiate($order, PaymentMethod::Card);
        } finally {
            $this->assertDatabaseHas('payments', [
                'order_id' => $order->id,
                'method' => PaymentMethod::Card->value,
                'status' => PaymentStatus::Failed->value,
            ]);
            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => OrderStatus::PaymentFailed->value,
            ]);
        }
    }

    public function test_preserves_fincode_error_when_recovery_fail_with_order_throws(): void
    {
        Log::spy();

        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_recovery_fail_tenant',
        ]);
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::PendingPayment,
        ]);

        $this->mockCardService
            ->shouldReceive('initiateCardPayment')
            ->once()
            ->andReturnUsing(function (Payment $payment, Order $order, ?int $cardId = null): never {
                // markAsFailed() を失敗させるため、遷移不能な終端状態を先に作る
                $payment->status = PaymentStatus::Completed;
                $payment->save();

                throw new FincodeApiException(
                    errorCode: 'E01100001',
                    response: ['error' => 'temporary'],
                    message: 'fincode temporary error'
                );
            });

        try {
            $this->service->initiate($order, PaymentMethod::Card);
            $this->fail('Expected PaymentFailedException was not thrown.');
        } catch (PaymentFailedException $e) {
            $this->assertSame('E01100001', $e->fincodeErrorCode);
            $this->assertSame('決済の開始に失敗しました。', $e->getMessage());
        }

        // trait の markPaymentAndOrderAsFailed がトランザクション失敗をログ
        Log::shouldHaveReceived('error')
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Failed to mark payment and order as failed'
                    && $context['reason'] === 'Failed to initiate payment'
                    && $context['error_class'] === \InvalidArgumentException::class
                    && str_contains($context['error_message'], 'Cannot transition');
            });

        // trait の handleFincodeException が fincode エラーをログ
        Log::shouldHaveReceived('error')
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Failed to initiate payment'
                    && $context['fincode_error'] === 'E01100001';
            });
    }
}

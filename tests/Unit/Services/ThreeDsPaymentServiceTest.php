<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\CardRegistrationException;
use App\Exceptions\PaymentFailedException;
use App\Models\FincodeCard;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Fincode\FincodeClient;
use App\Services\Fincode\FincodePaymentResponse;
use App\Services\FincodeCustomerService;
use App\Services\ThreeDsPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ThreeDsPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private FincodeClient|MockInterface $mockClient;

    private FincodeCustomerService|MockInterface $mockCustomerService;

    private ThreeDsPaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(FincodeClient::class);
        $this->mockCustomerService = Mockery::mock(FincodeCustomerService::class);
        $this->service = new ThreeDsPaymentService($this->mockClient, $this->mockCustomerService);
    }

    public function test_executes_payment_with_checkout_callback_url(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_3ds_tenant',
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
            'fincode_id' => 'p_exec_3ds',
            'fincode_access_id' => 'a_exec_3ds',
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::Card,
        ]);
        $expectedCallbackPath = "/order/checkout/callback/3ds/{$payment->id}";

        $this->mockClient
            ->shouldReceive('executeCardPaymentFor3ds')
            ->once()
            ->with('p_exec_3ds', Mockery::on(function ($params) use ($expectedCallbackPath) {
                return $params['access_id'] === 'a_exec_3ds'
                    && $params['tenant_shop_id'] === 's_3ds_tenant'
                    && $params['token'] === 'tok_3ds_123'
                    && isset($params['tds2_ret_url'])
                    && str_contains((string) $params['tds2_ret_url'], $expectedCallbackPath);
            }))
            ->andReturn(new FincodePaymentResponse(
                id: 'p_exec_3ds',
                accessId: 'a_exec_3ds',
                status: 'AWAITING_AUTHENTICATION',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
                tds2TransResult: null,
                challengeUrl: null,
                acsUrl: 'https://acs.example.com/3ds-method',
            ));

        $acsUrl = $this->service->executePayment($payment, 'tok_3ds_123');

        $this->assertEquals('https://acs.example.com/3ds-method', $acsUrl);
    }

    public function test_execute_payment_registers_card_and_pays_with_card_id(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_sync_card_tenant',
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
            'fincode_id' => 'p_sync_card',
            'fincode_access_id' => 'a_sync_card',
            'fincode_customer_id' => 'cus_sync_card',
            'fincode_card_id' => null,
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::Card,
        ]);

        $registeredCard = FincodeCard::factory()->make([
            'fincode_card_id' => 'card_registered_123',
        ]);

        $this->mockCustomerService
            ->shouldReceive('registerCustomerWithCard')
            ->once()
            ->with(
                Mockery::on(fn ($u) => $u->id === $user->id),
                Mockery::on(fn ($t) => $t->id === $tenant->id),
                'tok_sync_card',
                true
            )
            ->andReturn($registeredCard);

        $this->mockClient
            ->shouldReceive('executeCardPaymentFor3ds')
            ->once()
            ->with('p_sync_card', Mockery::on(function ($params) {
                return $params['access_id'] === 'a_sync_card'
                    && $params['tenant_shop_id'] === 's_sync_card_tenant'
                    && $params['customer_id'] === 'cus_sync_card'
                    && $params['card_id'] === 'card_registered_123'
                    && ! isset($params['token']);
            }))
            ->andReturn(new FincodePaymentResponse(
                id: 'p_sync_card',
                accessId: 'a_sync_card',
                status: 'AWAITING_AUTHENTICATION',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
                tds2TransResult: null,
                challengeUrl: null,
                acsUrl: 'https://acs.example.com/3ds-sync-card',
            ));

        $acsUrl = $this->service->executePayment($payment, 'tok_sync_card', true, true);

        $this->assertEquals('https://acs.example.com/3ds-sync-card', $acsUrl);

        $payment->refresh();
        $this->assertEquals('card_registered_123', $payment->fincode_card_id);
    }

    public function test_execute_payment_falls_back_to_token_when_card_registration_fails_without_consuming_token(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_fallback_tenant',
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
            'fincode_id' => 'p_fallback',
            'fincode_access_id' => 'a_fallback',
            'fincode_customer_id' => 'cus_fallback',
            'fincode_card_id' => null,
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::Card,
        ]);

        $this->mockCustomerService
            ->shouldReceive('registerCustomerWithCard')
            ->once()
            ->andThrow(new CardRegistrationException('E001001', '顧客登録に失敗しました。', tokenConsumed: false));

        $this->mockClient
            ->shouldReceive('executeCardPaymentFor3ds')
            ->once()
            ->with('p_fallback', Mockery::on(function ($params) {
                return $params['access_id'] === 'a_fallback'
                    && $params['token'] === 'tok_fallback'
                    && $params['customer_id'] === 'cus_fallback'
                    && ! isset($params['card_id']);
            }))
            ->andReturn(new FincodePaymentResponse(
                id: 'p_fallback',
                accessId: 'a_fallback',
                status: 'AWAITING_AUTHENTICATION',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
                tds2TransResult: null,
                challengeUrl: null,
                acsUrl: 'https://acs.example.com/3ds-fallback',
            ));

        $acsUrl = $this->service->executePayment($payment, 'tok_fallback', true, false);

        $this->assertEquals('https://acs.example.com/3ds-fallback', $acsUrl);

        $payment->refresh();
        $this->assertNull($payment->fincode_card_id);
    }

    public function test_execute_payment_throws_when_card_registration_fails_with_token_consumed(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_consumed_tenant',
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
            'fincode_id' => 'p_consumed',
            'fincode_access_id' => 'a_consumed',
            'fincode_customer_id' => 'cus_consumed',
            'fincode_card_id' => null,
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::Card,
        ]);

        $this->mockCustomerService
            ->shouldReceive('registerCustomerWithCard')
            ->once()
            ->andThrow(new CardRegistrationException('E01100101', 'カード登録に失敗しました。', tokenConsumed: true));

        $this->mockClient
            ->shouldNotReceive('executeCardPaymentFor3ds');

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('カード登録に失敗しました。もう一度お試しください。');

        $this->service->executePayment($payment, 'tok_consumed', true, false);
    }

    public function test_execute_payment_passes_customer_id_for_new_card(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_3ds_cust_tenant',
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
            'fincode_id' => 'p_3ds_cust',
            'fincode_access_id' => 'a_3ds_cust',
            'fincode_customer_id' => 'cus_for_3ds',
            'fincode_card_id' => null,
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::Card,
        ]);

        $this->mockClient
            ->shouldReceive('executeCardPaymentFor3ds')
            ->once()
            ->with('p_3ds_cust', Mockery::on(function ($params) {
                return $params['access_id'] === 'a_3ds_cust'
                    && $params['tenant_shop_id'] === 's_3ds_cust_tenant'
                    && $params['token'] === 'tok_new_card'
                    && $params['customer_id'] === 'cus_for_3ds';
            }))
            ->andReturn(new FincodePaymentResponse(
                id: 'p_3ds_cust',
                accessId: 'a_3ds_cust',
                status: 'AWAITING_AUTHENTICATION',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
                tds2TransResult: null,
                challengeUrl: null,
                acsUrl: 'https://acs.example.com/3ds-new-card',
            ));

        $acsUrl = $this->service->executePayment($payment, 'tok_new_card');

        $this->assertEquals('https://acs.example.com/3ds-new-card', $acsUrl);
    }

    public function test_execute_authentication_returns_requires_challenge_when_additional_auth_is_needed(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_challenge_tenant',
        ]);
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => User::factory()->create()->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'fincode_id' => 'p_challenge',
            'fincode_access_id' => 'a_challenge',
            'status' => PaymentStatus::Processing,
            'method' => PaymentMethod::Card,
        ]);

        $this->mockClient
            ->shouldReceive('execute3dsAuthentication')
            ->once()
            ->with('a_challenge', 'auth_param', 's_challenge_tenant')
            ->andReturn(new FincodePaymentResponse(
                id: 'p_challenge',
                accessId: 'a_challenge',
                status: 'AWAITING_AUTHENTICATION',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
                tds2TransResult: 'C',
                challengeUrl: 'https://acs.example.com/challenge',
                acsUrl: null,
            ));

        $result = $this->service->executeAuthentication($payment, 'auth_param');

        $payment->refresh();
        $this->assertTrue($result->requiresRedirect());
        $this->assertSame('https://acs.example.com/challenge', $result->challengeUrl);
        $this->assertSame('C', $payment->tds_trans_result);
        $this->assertSame('https://acs.example.com/challenge', $payment->tds_challenge_url);
    }

    public function test_execute_authentication_completes_payment_after_frictionless_success(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_auth_success_tenant',
        ]);
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => User::factory()->create()->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'fincode_id' => 'p_auth_success',
            'fincode_access_id' => 'a_auth_success',
            'status' => PaymentStatus::Processing,
            'method' => PaymentMethod::Card,
        ]);

        $this->mockClient
            ->shouldReceive('execute3dsAuthentication')
            ->once()
            ->with('a_auth_success', 'auth_param', 's_auth_success_tenant')
            ->andReturn(new FincodePaymentResponse(
                id: 'p_auth_success',
                accessId: 'a_auth_success',
                status: 'AUTHENTICATED',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
                tds2TransResult: 'Y',
                challengeUrl: null,
                acsUrl: null,
            ));

        $this->mockClient
            ->shouldReceive('executePaymentAfter3ds')
            ->once()
            ->with('p_auth_success', 'a_auth_success', 's_auth_success_tenant')
            ->andReturn(new FincodePaymentResponse(
                id: 'p_auth_success',
                accessId: 'a_auth_success',
                status: 'CAPTURED',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
            ));

        $result = $this->service->executeAuthentication($payment, 'auth_param');

        $payment->refresh();
        $order->refresh();

        $this->assertTrue($result->isAuthenticated());
        $this->assertSame(PaymentStatus::Completed, $payment->status);
        $this->assertSame(OrderStatus::Paid, $order->status);
    }

    public function test_confirm_and_execute_marks_payment_failed_when_challenge_authentication_is_rejected(): void
    {
        $tenant = Tenant::factory()->create([
            'fincode_shop_id' => 's_confirm_failed_tenant',
        ]);
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => User::factory()->create()->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'fincode_id' => 'p_confirm_failed',
            'fincode_access_id' => 'a_confirm_failed',
            'status' => PaymentStatus::Processing,
            'method' => PaymentMethod::Card,
        ]);

        $this->mockClient
            ->shouldReceive('get3dsAuthenticationResult')
            ->once()
            ->with('a_confirm_failed', 's_confirm_failed_tenant')
            ->andReturn(new FincodePaymentResponse(
                id: 'p_confirm_failed',
                accessId: 'a_confirm_failed',
                status: 'AUTH_FAILED',
                amount: 1000,
                payType: 'Card',
                linkUrl: null,
                clientField1: null,
                errorCode: null,
                rawResponse: [],
                tds2TransResult: 'N',
                challengeUrl: null,
                acsUrl: null,
            ));

        $result = $this->service->confirmAndExecute($payment, 'challenge_param');

        $payment->refresh();
        $order->refresh();

        $this->assertTrue($result->isFailed());
        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertSame(OrderStatus::PaymentFailed, $order->status);
        $this->assertSame('N', $payment->tds_trans_result);
    }

    public function test_execute_authentication_fails_fast_when_access_id_is_missing(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => User::factory()->create()->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'fincode_id' => 'p_missing_access',
            'fincode_access_id' => null,
            'status' => PaymentStatus::Processing,
            'method' => PaymentMethod::Card,
        ]);

        $this->mockClient
            ->shouldNotReceive('execute3dsAuthentication');

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('fincode access_idが設定されていません。');

        try {
            $this->service->executeAuthentication($payment, 'auth_param');
        } finally {
            $payment->refresh();
            $order->refresh();

            $this->assertSame(PaymentStatus::Failed, $payment->status);
            $this->assertSame(OrderStatus::PaymentFailed, $order->status);
        }
    }
}

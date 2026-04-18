<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Checkout\CheckoutOrchestrator;
use App\Services\TenantContext;
use App\Services\ThreeDsAuthResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Mockery;
use Tests\TestCase;

class ThreeDSecureWebCallbackTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createPaymentFor3ds(
        ?User $user = null,
        PaymentStatus $paymentStatus = PaymentStatus::Processing,
    ): Payment {
        $user = $user ?? $this->customer;

        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 1500,
        ]);

        return Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'method' => PaymentMethod::Card,
            'status' => $paymentStatus,
            'amount' => 1500,
            'fincode_id' => 'fin_3ds_web_123',
            'fincode_access_id' => 'acc_3ds_web_123',
        ]);
    }

    private function buildSignedCallbackUrl(Payment $payment, array $extraParams = []): string
    {
        $url = URL::temporarySignedRoute(
            'order.checkout.callback.3ds',
            now()->addMinutes(30),
            ['payment' => $payment->id]
        );

        if (! empty($extraParams)) {
            $url .= '&'.http_build_query($extraParams);
        }

        return $url;
    }

    public function test_web_3ds_callback_sets_tenant_context_from_payment(): void
    {
        $payment = $this->createPaymentFor3ds();

        $tenantContext = $this->app->make(TenantContext::class);
        $this->assertNull($tenantContext->getTenantId());

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('process3dsCallback')
            ->once()
            ->withArgs(function ($p, $param) use ($payment) {
                // process3dsCallback 呼び出し時点でTenantContextが設定されていることを検証
                $context = app(TenantContext::class);
                $this->assertEquals($this->tenant->id, $context->getTenantId());

                return $p->id === $payment->id && $param === 'tds_param_123';
            })
            ->andReturn(ThreeDsAuthResult::authenticated($payment));

        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $url = $this->buildSignedCallbackUrl($payment, ['param' => 'tds_param_123', 'event' => 'AuthResultReady']);

        $response = $this->actingAs($this->customer)
            ->get($url);

        $response->assertRedirect(route('order.checkout.complete', ['order' => $payment->order_id]));

        // リクエスト後もTenantContextが正しく設定されていることを確認
        $this->assertEquals($this->tenant->id, $tenantContext->getTenantId());
    }

    public function test_web_3ds_callback_succeeds_when_webhook_already_completed_payment(): void
    {
        // Webhook先行で既にCompleted状態になっているPayment
        $payment = $this->createPaymentFor3ds(paymentStatus: PaymentStatus::Completed);

        // 完了済みなのでOrchestratorは呼ばれず、terminal stateチェックで早期リターン
        $url = $this->buildSignedCallbackUrl($payment, ['param' => 'tds_param_123', 'event' => 'AuthResultReady']);

        $response = $this->actingAs($this->customer)
            ->get($url);

        // 完了済みの決済は「既に処理済み」としてfailed画面にリダイレクトされる
        $response->assertRedirect();
        $this->assertStringContainsString('order/checkout/failed', $response->headers->get('Location'));
    }

    public function test_web_3ds_callback_auto_login_and_completes_payment(): void
    {
        $payment = $this->createPaymentFor3ds();

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('process3dsCallback')
            ->once()
            ->andReturn(ThreeDsAuthResult::authenticated($payment));

        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $url = $this->buildSignedCallbackUrl($payment, ['param' => 'tds_param_123', 'event' => 'AuthResultReady']);

        // 未ログイン状態で署名付きURLにアクセス → 自動ログインが行われる
        $response = $this->post($url, ['param' => 'tds_param_123', 'event' => 'AuthResultReady']);

        $response->assertRedirect(route('order.checkout.complete', ['order' => $payment->order_id]));
        $this->assertAuthenticatedAs($this->customer);
    }

    public function test_web_3ds_callback_rejects_authenticated_user_who_does_not_own_payment(): void
    {
        $payment = $this->createPaymentFor3ds();
        $otherCustomer = User::factory()->customer()->create();

        $url = $this->buildSignedCallbackUrl($payment, ['param' => 'tds_param_123', 'event' => 'AuthResultReady']);

        $response = $this->actingAs($otherCustomer)
            ->get($url);

        $response->assertRedirect();
        $this->assertStringContainsString('error=unauthorized', $response->headers->get('Location'));
    }

    public function test_web_3ds_callback_rejects_auto_login_when_it_has_already_been_consumed(): void
    {
        $payment = $this->createPaymentFor3ds();
        Cache::put("3ds_auto_login_consumed:{$payment->id}", true, 300);

        $url = $this->buildSignedCallbackUrl($payment, ['param' => 'tds_param_123', 'event' => 'AuthResultReady']);

        $response = $this->post($url, ['param' => 'tds_param_123', 'event' => 'AuthResultReady']);

        $response->assertRedirect();
        $this->assertStringContainsString('error=already_processed', $response->headers->get('Location'));
        $this->assertGuest();
    }

    public function test_web_3ds_callback_redirects_to_failed_when_param_is_missing(): void
    {
        $payment = $this->createPaymentFor3ds();
        $url = $this->buildSignedCallbackUrl($payment);

        $response = $this->actingAs($this->customer)
            ->get($url);

        $response->assertRedirect();
        $this->assertStringContainsString('error=3ds_missing_param', $response->headers->get('Location'));
    }

    public function test_web_3ds_callback_redirects_to_challenge_url(): void
    {
        $payment = $this->createPaymentFor3ds();
        $challengeUrl = 'https://acs.example.com/challenge';

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('process3dsCallback')
            ->once()
            ->andReturn(ThreeDsAuthResult::requiresChallenge($payment, $challengeUrl));

        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $url = $this->buildSignedCallbackUrl($payment, ['param' => 'tds_param_123']);

        $response = $this->actingAs($this->customer)
            ->get($url);

        $response->assertRedirect($challengeUrl);
    }

    public function test_web_3ds_callback_redirects_to_failed_on_auth_failure(): void
    {
        $payment = $this->createPaymentFor3ds();

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('process3dsCallback')
            ->once()
            ->andReturn(ThreeDsAuthResult::failed($payment));

        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $url = $this->buildSignedCallbackUrl($payment, ['param' => 'tds_param_123', 'event' => 'AuthResultReady']);

        $response = $this->actingAs($this->customer)
            ->get($url);

        $response->assertRedirect();
        $this->assertStringContainsString('order/checkout/failed', $response->headers->get('Location'));
    }
}

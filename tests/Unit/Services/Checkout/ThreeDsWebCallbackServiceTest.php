<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Checkout\CheckoutOrchestrator;
use App\Services\Checkout\ThreeDsWebCallbackService;
use App\Services\TenantContext;
use App\Services\ThreeDsAuthResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ThreeDsWebCallbackServiceTest extends TestCase
{
    use RefreshDatabase;

    private CheckoutOrchestrator|MockInterface $mockOrchestrator;

    private ThreeDsWebCallbackService $service;

    private User $customer;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $this->service = new ThreeDsWebCallbackService(
            $this->mockOrchestrator,
            $this->app->make(TenantContext::class),
        );

        $this->customer = User::factory()->customer()->create();
        $this->tenant = Tenant::factory()->create([
            'is_active' => true,
            'fincode_shop_id' => 'shop_test',
        ]);
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        Mockery::close();

        parent::tearDown();
    }

    public function test_handle_normalizes_legacy_md_callback_before_invoking_orchestrator(): void
    {
        $payment = $this->createPaymentFor3ds();
        $this->actingAs($this->customer);

        $this->mockOrchestrator
            ->shouldReceive('process3dsCallback')
            ->once()
            ->withArgs(function ($targetPayment, $param, $event) use ($payment) {
                return $targetPayment->id === $payment->id
                    && $param === 'legacy_md_param'
                    && $event === 'AuthResultReady';
            })
            ->andReturn(ThreeDsAuthResult::authenticated($payment));

        $request = Request::create(
            "/order/checkout/callback/3ds/{$payment->id}",
            'GET',
            ['MD' => 'legacy_md_param']
        );

        $outcome = $this->service->handle($request, $payment);

        $this->assertFalse($outcome->hasError());
        $this->assertTrue($outcome->result()?->isAuthenticated());
        $this->assertSame($this->tenant->id, app(TenantContext::class)->getTenantId());
    }

    public function test_handle_returns_failure_when_auto_login_has_already_been_consumed(): void
    {
        $payment = $this->createPaymentFor3ds();
        Cache::put("3ds_auto_login_consumed:{$payment->id}", true, 300);

        $this->mockOrchestrator
            ->shouldNotReceive('process3dsCallback');

        $request = Request::create(
            "/order/checkout/callback/3ds/{$payment->id}",
            'POST',
            ['param' => 'callback_param', 'event' => 'AuthResultReady']
        );

        $outcome = $this->service->handle($request, $payment);

        $this->assertTrue($outcome->hasError());
        $this->assertSame('already_processed', $outcome->errorKey());
    }

    public function test_handle_returns_failure_when_param_is_missing(): void
    {
        $payment = $this->createPaymentFor3ds();
        $this->actingAs($this->customer);

        $this->mockOrchestrator
            ->shouldNotReceive('process3dsCallback');

        $request = Request::create(
            "/order/checkout/callback/3ds/{$payment->id}",
            'GET'
        );

        $outcome = $this->service->handle($request, $payment);

        $this->assertTrue($outcome->hasError());
        $this->assertSame('3ds_missing_param', $outcome->errorKey());
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
            'fincode_id' => 'fin_3ds_unit_123',
            'fincode_access_id' => 'acc_3ds_unit_123',
        ]);
    }
}

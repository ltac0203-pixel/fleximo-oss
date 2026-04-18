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
use App\Services\ThreeDsAuthResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ThreeDSecureTest extends TestCase
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
            'fincode_id' => 'fin_3ds_123',
            'fincode_access_id' => 'acc_3ds_123',
        ]);
    }

    public function test_3ds_callback_completes_payment_on_success(): void
    {
        $payment = $this->createPaymentFor3ds();

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('process3dsCallback')
            ->once()
            ->withArgs(function ($p, $param) use ($payment) {
                return $p->id === $payment->id && $param === 'tds_param_123';
            })
            ->andReturn(ThreeDsAuthResult::authenticated($payment));

        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/3ds-callback', [
                'payment_id' => $payment->id,
                'param' => 'tds_param_123',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['order'],
            ]);
    }

    public function test_3ds_callback_returns_400_on_auth_failure(): void
    {
        $payment = $this->createPaymentFor3ds();

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('process3dsCallback')
            ->once()
            ->andReturn(ThreeDsAuthResult::failed($payment));

        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/3ds-callback', [
                'payment_id' => $payment->id,
                'param' => 'tds_param_123',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('error.message', '3DS認証に失敗しました。別のカードをお試しください。');
    }

    public function test_3ds_callback_rejects_other_users_payment(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $payment = $this->createPaymentFor3ds(user: $otherCustomer);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/3ds-callback', [
                'payment_id' => $payment->id,
                'param' => 'tds_param_123',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_id'])
            ->assertJsonPath('errors.payment_id.0', '指定された決済は無効です。');
    }

    public function test_3ds_callback_rejects_nonexistent_payment_id(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/3ds-callback', [
                'payment_id' => 999999,
                'param' => 'tds_param_123',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_id'])
            ->assertJsonPath('errors.payment_id.0', '指定された決済は無効です。');
    }

    public function test_3ds_callback_requires_param(): void
    {
        $payment = $this->createPaymentFor3ds();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/3ds-callback', [
                'payment_id' => $payment->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['param']);
    }

    public function test_3ds_callback_requires_payment_id(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/3ds-callback', [
                'param' => 'tds_param_123',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_id']);
    }

    public function test_3ds_callback_requires_authentication(): void
    {
        $response = $this->withIdempotencyKey()
            ->postJson('/api/customer/payments/3ds-callback', [
                'payment_id' => 1,
                'param' => 'tds_param_123',
            ]);

        $response->assertUnauthorized();
    }

    public function test_3ds_callback_accepts_pending_payment_when_orchestrator_handles_it(): void
    {
        $payment = $this->createPaymentFor3ds(paymentStatus: PaymentStatus::Pending);

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('process3dsCallback')
            ->once()
            ->andReturn(ThreeDsAuthResult::authenticated($payment));

        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/3ds-callback', [
                'payment_id' => $payment->id,
                'param' => 'tds_param_123',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'order',
                ],
            ]);
    }

    public function test_3ds_callback_rejects_terminal_payment(): void
    {
        $payment = $this->createPaymentFor3ds(paymentStatus: PaymentStatus::Completed);

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('process3dsCallback')
            ->once()
            ->andThrow(new \App\Exceptions\PaymentFailedException(
                $payment,
                null,
                'この決済は3DS認証待ちではありません。'
            ));

        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/3ds-callback', [
                'payment_id' => $payment->id,
                'param' => 'tds_param_123',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'この決済は3DS認証待ちではありません。');
    }
}

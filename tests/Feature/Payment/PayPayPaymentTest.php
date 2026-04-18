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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PayPayPaymentTest extends TestCase
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

    private function createPayPayPayment(
        ?User $user = null,
        PaymentStatus $paymentStatus = PaymentStatus::Pending,
    ): Payment {
        $user = $user ?? $this->customer;

        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
            'total_amount' => 800,
        ]);

        return Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'method' => PaymentMethod::PayPay,
            'status' => $paymentStatus,
            'amount' => 800,
            'fincode_id' => 'fin_paypay_123',
        ]);
    }

    public function test_finalize_without_token_confirms_paypay_payment(): void
    {
        $payment = $this->createPayPayPayment();

        $order = $payment->order;
        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('finalizePayment')
            ->once()
            ->withArgs(fn ($p) => $p->id === $payment->id)
            ->andReturn($order);

        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'payment_id' => $payment->id,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['order'],
            ]);
    }

    public function test_finalize_paypay_rejects_other_users_payment(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $payment = $this->createPayPayPayment(user: $otherCustomer);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'payment_id' => $payment->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_id']);
    }

    public function test_finalize_paypay_returns_order_when_already_completed(): void
    {
        $payment = $this->createPayPayPayment(
            paymentStatus: PaymentStatus::Completed,
        );

        $order = $payment->order;
        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('finalizePayment')
            ->once()
            ->withArgs(fn ($p) => $p->id === $payment->id)
            ->andReturn($order);

        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'payment_id' => $payment->id,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['order'],
            ]);
    }

    public function test_finalize_paypay_requires_authentication(): void
    {
        $response = $this->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'payment_id' => 1,
            ]);

        $response->assertUnauthorized();
    }
}

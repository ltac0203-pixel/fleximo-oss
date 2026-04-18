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

class CreditCardPaymentTest extends TestCase
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

    private function createOrderWithPayment(
        ?User $user = null,
        PaymentStatus $paymentStatus = PaymentStatus::Pending,
        OrderStatus $orderStatus = OrderStatus::PendingPayment,
    ): Payment {
        $user = $user ?? $this->customer;

        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'status' => $orderStatus,
            'total_amount' => 1000,
        ]);

        return Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'method' => PaymentMethod::Card,
            'status' => $paymentStatus,
            'amount' => 1000,
            'fincode_id' => 'fin_test_123',
            'fincode_access_id' => 'acc_test_123',
        ]);
    }

    public function test_finalize_with_token_returns_acs_url_redirect(): void
    {
        $payment = $this->createOrderWithPayment();

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('executePaymentFor3ds')
            ->once()
            ->withArgs(function ($p, $token) use ($payment) {
                return $p->id === $payment->id && $token === 'card_token_123';
            })
            ->andReturn('https://acs.example.com/3ds-method');

        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'payment_id' => $payment->id,
                'token' => 'card_token_123',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.requires_3ds_redirect', true)
            ->assertJsonPath('data.redirect_url', 'https://acs.example.com/3ds-method')
            ->assertJsonPath('data.payment_id', $payment->id);
    }

    public function test_finalize_with_saved_card_returns_acs_url_redirect(): void
    {
        $payment = $this->createOrderWithPayment();
        $payment->fincode_customer_id = 'cust_123';
        $payment->fincode_card_id = 'card_456';
        $payment->save();

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('executePaymentFor3ds')
            ->once()
            ->withArgs(function ($p, $token) use ($payment) {
                return $p->id === $payment->id && $token === null;
            })
            ->andReturn('https://acs.example.com/3ds-method');

        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'payment_id' => $payment->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.requires_3ds_redirect', true)
            ->assertJsonPath('data.redirect_url', 'https://acs.example.com/3ds-method')
            ->assertJsonPath('data.payment_id', $payment->id);
    }

    public function test_finalize_rejects_other_users_payment(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $payment = $this->createOrderWithPayment(user: $otherCustomer);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'payment_id' => $payment->id,
                'token' => 'card_token_123',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_id'])
            ->assertJsonPath('errors.payment_id.0', '指定された決済は無効です。');
    }

    public function test_finalize_rejects_nonexistent_payment_id(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'payment_id' => 999999,
                'token' => 'card_token_123',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_id'])
            ->assertJsonPath('errors.payment_id.0', '指定された決済は無効です。');
    }

    public function test_finalize_rejects_completed_payment(): void
    {
        $payment = $this->createOrderWithPayment(
            paymentStatus: PaymentStatus::Completed,
            orderStatus: OrderStatus::Paid,
        );

        $mockOrchestrator = Mockery::mock(CheckoutOrchestrator::class);
        $mockOrchestrator->shouldReceive('executePaymentFor3ds')
            ->once()
            ->andThrow(new \App\Exceptions\PaymentFailedException(
                $payment,
                null,
                'この決済は既に処理済みです。'
            ));

        $this->app->instance(CheckoutOrchestrator::class, $mockOrchestrator);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'payment_id' => $payment->id,
                'token' => 'card_token_123',
            ]);

        $response->assertStatus(422);
    }

    public function test_finalize_requires_authentication(): void
    {
        $response = $this->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'payment_id' => 1,
                'token' => 'card_token_123',
            ]);

        $response->assertUnauthorized();
    }

    public function test_finalize_requires_payment_id(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->withIdempotencyKey()
            ->postJson('/api/customer/payments/finalize', [
                'token' => 'card_token_123',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_id']);
    }
}

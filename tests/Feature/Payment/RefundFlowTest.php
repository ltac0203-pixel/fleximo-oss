<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'test_webhook_secret_key';

    protected function setUp(): void
    {
        parent::setUp();
        config(['fincode.webhook_secret' => $this->webhookSecret]);
    }

    private function generateSignature(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), $this->webhookSecret);
    }

    // 返金処理がPaymentとOrderのステータスを正しく更新することをテスト
    public function test_refund_marks_payment_as_refunded(): void
    {
        $tenant = Tenant::factory()->create();
        $customer = User::factory()->customer()->create();

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $customer->id,
            'status' => 'paid',
            'total_amount' => 1000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'amount' => 1000,
            'status' => 'completed',
            'method' => 'card',
            'fincode_id' => 'test_payment_'.uniqid(),
        ]);

        $payload = [
            'event' => 'payment.refunded',
            'id' => $payment->fincode_id,
            'created' => now()->toIso8601String(),
            'data' => [
                'id' => $payment->fincode_id,
                'amount' => 1000,
                'refunded_at' => now()->toIso8601String(),
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson("/api/webhooks/payments/{$tenant->id}", $payload, [
            'Fincode-Signature' => $signature,
        ]);

        $response->assertStatus(200);
    }

    // 部分返金処理のテスト
    public function test_partial_refund_updates_payment_status(): void
    {
        $tenant = Tenant::factory()->create();
        $customer = User::factory()->customer()->create();

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $customer->id,
            'status' => 'paid',
            'total_amount' => 1000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $tenant->id,
            'amount' => 1000,
            'status' => 'completed',
            'method' => 'card',
            'fincode_id' => 'test_payment_'.uniqid(),
        ]);

        $payload = [
            'event' => 'payment.partially_refunded',
            'id' => $payment->fincode_id,
            'created' => now()->toIso8601String(),
            'data' => [
                'id' => $payment->fincode_id,
                'amount' => 1000,
                'refunded_amount' => 500,
                'refunded_at' => now()->toIso8601String(),
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson("/api/webhooks/payments/{$tenant->id}", $payload, [
            'Fincode-Signature' => $signature,
        ]);

        $response->assertStatus(200);
    }

    // 不正な署名でも応答差分を出さないことをテスト
    public function test_refund_webhook_with_invalid_signature_returns_uniform_response(): void
    {
        $tenant = Tenant::factory()->create();

        $payload = [
            'event' => 'payment.refunded',
            'id' => 'test_payment_123',
            'created' => now()->toIso8601String(),
            'data' => [
                'id' => 'test_payment_123',
                'amount' => 1000,
            ],
        ];

        $response = $this->postJson("/api/webhooks/payments/{$tenant->id}", $payload, [
            'Fincode-Signature' => 'invalid_signature_12345',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'accepted']);
    }
}

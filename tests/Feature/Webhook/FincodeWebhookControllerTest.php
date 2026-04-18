<?php

declare(strict_types=1);

namespace Tests\Feature\Webhook;

use App\Jobs\ProcessPaymentWebhookJob;
use App\Models\Tenant;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function now;

class FincodeWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'test-webhook-secret';

    private const TEST_SHOP_ID = 'shop_test_12345';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('fincode.webhook_secret', $this->webhookSecret);
    }

    private function generateSignature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->webhookSecret);
    }

    private function createTenantWithShop(array $overrides = []): Tenant
    {
        return Tenant::factory()->create(array_merge([
            'fincode_shop_id' => self::TEST_SHOP_ID,
        ], $overrides));
    }

    private function makePayload(array $data): string
    {
        $data['shop_id'] = $data['shop_id'] ?? self::TEST_SHOP_ID;
        $data['created'] = $data['created'] ?? now()->toIso8601String();

        return json_encode($data);
    }

    #[Test]
    public function it_accepts_valid_webhook(): void
    {
        Queue::fake();

        $tenant = $this->createTenantWithShop();

        $payload = $this->makePayload([
            'event' => 'payment.completed',
            'id' => 'pay_valid_123',
            'amount' => 1000,
        ]);

        $response = $this->postJson(
            "/api/webhooks/payments/{$tenant->id}",
            json_decode($payload, true),
            [
                'Fincode-Signature' => $this->generateSignature($payload),
                'Content-Type' => 'application/json',
            ]
        );

        $response->assertStatus(200)
            ->assertJson(['status' => 'accepted']);

        $this->assertDatabaseHas('webhook_logs', [
            'tenant_id' => $tenant->id,
            'fincode_id' => 'pay_valid_123',
            'event_type' => 'payment.completed',
        ]);

        Queue::assertPushed(ProcessPaymentWebhookJob::class);
    }

    #[Test]
    public function it_returns_200_for_unknown_tenant(): void
    {
        $payload = $this->makePayload([
            'event' => 'payment.completed',
            'id' => 'pay_unknown_tenant',
        ]);

        $response = $this->postJson(
            '/api/webhooks/payments/99999',
            json_decode($payload, true),
            [
                'Fincode-Signature' => $this->generateSignature($payload),
            ]
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'accepted',
            ]);
    }

    #[Test]
    public function it_returns_uniform_response_for_invalid_signature(): void
    {
        $tenant = Tenant::factory()->create();

        $response = $this->postJson(
            "/api/webhooks/payments/{$tenant->id}",
            [
                'event' => 'payment.completed',
                'id' => 'pay_invalid_sig',
            ],
            [
                'Fincode-Signature' => 'invalid-signature',
            ]
        );

        $response->assertStatus(200)
            ->assertJson(['status' => 'accepted']);

        $this->assertDatabaseMissing('webhook_logs', [
            'tenant_id' => $tenant->id,
            'fincode_id' => 'pay_invalid_sig',
        ]);
    }

    #[Test]
    public function it_ignores_duplicate_webhook(): void
    {
        Queue::fake();

        $tenant = $this->createTenantWithShop();
        $fincodeId = 'pay_duplicate_123';

        WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => $fincodeId,
            'event_type' => 'payment.completed',
            'payload' => ['id' => $fincodeId],
        ]);

        $payload = $this->makePayload([
            'event' => 'payment.completed',
            'id' => $fincodeId,
        ]);

        $response = $this->postJson(
            "/api/webhooks/payments/{$tenant->id}",
            json_decode($payload, true),
            [
                'Fincode-Signature' => $this->generateSignature($payload),
            ]
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'accepted',
            ]);

        Queue::assertNotPushed(ProcessPaymentWebhookJob::class);
    }

    #[Test]
    public function it_returns_uniform_response_when_secret_not_configured(): void
    {
        Queue::fake();
        Config::set('fincode.webhook_secret', null);

        $tenant = Tenant::factory()->create();

        $response = $this->postJson(
            "/api/webhooks/payments/{$tenant->id}",
            [
                'event' => 'payment.completed',
                'id' => 'pay_no_secret',
            ]
        );

        $response->assertStatus(200)
            ->assertJson(['status' => 'accepted']);

        Queue::assertNotPushed(ProcessPaymentWebhookJob::class);
    }

    #[Test]
    public function it_creates_webhook_log_with_correct_data(): void
    {
        Queue::fake();

        $tenant = $this->createTenantWithShop();

        $payload = $this->makePayload([
            'event' => 'payment.failed',
            'id' => 'pay_log_test',
            'error_code' => 'CARD_DECLINED',
        ]);

        $this->postJson(
            "/api/webhooks/payments/{$tenant->id}",
            json_decode($payload, true),
            [
                'Fincode-Signature' => $this->generateSignature($payload),
            ]
        );

        $webhookLog = WebhookLog::where('fincode_id', 'pay_log_test')->first();

        $this->assertNotNull($webhookLog);
        $this->assertEquals($tenant->id, $webhookLog->tenant_id);
        $this->assertEquals('fincode', $webhookLog->provider);
        $this->assertEquals('payment.failed', $webhookLog->event_type);
        $this->assertEquals('CARD_DECLINED', $webhookLog->payload['error_code']);
        $this->assertFalse($webhookLog->processed);
    }

    #[Test]
    public function it_accepts_paypay_webhook_with_order_id(): void
    {
        Queue::fake();

        $tenant = $this->createTenantWithShop();

        $payload = $this->makePayload([
            'event' => 'payments.paypay.capture',
            'order_id' => 'paypay_order_123',
            'status' => 'CAPTURED',
        ]);

        $response = $this->postJson(
            "/api/webhooks/payments/{$tenant->id}",
            json_decode($payload, true),
            [
                'Fincode-Signature' => $this->generateSignature($payload),
                'Content-Type' => 'application/json',
            ]
        );

        $response->assertStatus(200)
            ->assertJson(['status' => 'accepted']);

        $this->assertDatabaseHas('webhook_logs', [
            'tenant_id' => $tenant->id,
            'fincode_id' => 'paypay_order_123',
            'event_type' => 'payments.paypay.capture',
        ]);

        Queue::assertPushed(ProcessPaymentWebhookJob::class);
    }

    #[Test]
    public function it_prioritizes_payload_id_over_order_id_when_both_exist(): void
    {
        Queue::fake();

        $tenant = $this->createTenantWithShop();

        $payload = $this->makePayload([
            'event' => 'payments.paypay.capture',
            'id' => 'paypay_primary_id_123',
            'order_id' => 'paypay_fallback_order_999',
            'status' => 'CAPTURED',
        ]);

        $response = $this->postJson(
            "/api/webhooks/payments/{$tenant->id}",
            json_decode($payload, true),
            [
                'Fincode-Signature' => $this->generateSignature($payload),
                'Content-Type' => 'application/json',
            ]
        );

        $response->assertStatus(200)
            ->assertJson(['status' => 'accepted']);

        $this->assertDatabaseHas('webhook_logs', [
            'tenant_id' => $tenant->id,
            'fincode_id' => 'paypay_primary_id_123',
            'event_type' => 'payments.paypay.capture',
        ]);
    }

    #[Test]
    public function it_handles_missing_fincode_id_in_payload(): void
    {
        Queue::fake();

        $tenant = $this->createTenantWithShop();

        $payload = $this->makePayload([
            'event' => 'payment.completed',

        ]);

        $response = $this->postJson(
            "/api/webhooks/payments/{$tenant->id}",
            json_decode($payload, true),
            [
                'Fincode-Signature' => $this->generateSignature($payload),
            ]
        );

        $response->assertStatus(200)
            ->assertJson(['status' => 'accepted']);

        $this->assertDatabaseHas('webhook_logs', [
            'tenant_id' => $tenant->id,
            'event_type' => 'payment.completed',
            'fincode_id' => null,
        ]);
    }

    #[Test]
    public function it_rejects_webhook_when_payload_shop_id_missing(): void
    {
        Queue::fake();

        $tenant = $this->createTenantWithShop();

        // makePayload のデフォルト shop_id を明示的に null に上書きする
        $data = [
            'event' => 'payment.completed',
            'id' => 'pay_no_shop_id',
            'shop_id' => null,
            'created' => now()->toIso8601String(),
        ];
        $payload = json_encode($data);

        $response = $this->postJson(
            "/api/webhooks/payments/{$tenant->id}",
            $data,
            ['Fincode-Signature' => $this->generateSignature($payload)]
        );

        $response->assertStatus(200)->assertJson(['status' => 'accepted']);

        $this->assertDatabaseMissing('webhook_logs', [
            'fincode_id' => 'pay_no_shop_id',
        ]);
        Queue::assertNotPushed(ProcessPaymentWebhookJob::class);
    }

    #[Test]
    public function it_rejects_webhook_when_tenant_has_no_shop_id(): void
    {
        Queue::fake();

        // fincode 未連携テナントへの webhook は常に拒否
        $tenant = Tenant::factory()->create(['fincode_shop_id' => null]);

        $payload = $this->makePayload([
            'event' => 'payment.completed',
            'id' => 'pay_unlinked_tenant',
        ]);

        $response = $this->postJson(
            "/api/webhooks/payments/{$tenant->id}",
            json_decode($payload, true),
            ['Fincode-Signature' => $this->generateSignature($payload)]
        );

        $response->assertStatus(200)->assertJson(['status' => 'accepted']);

        $this->assertDatabaseMissing('webhook_logs', [
            'fincode_id' => 'pay_unlinked_tenant',
        ]);
        Queue::assertNotPushed(ProcessPaymentWebhookJob::class);
    }

    #[Test]
    public function it_rejects_webhook_when_shop_id_does_not_match(): void
    {
        Queue::fake();

        $tenant = $this->createTenantWithShop();

        $payload = $this->makePayload([
            'event' => 'payment.completed',
            'id' => 'pay_shop_mismatch',
            'shop_id' => 'shop_other_tenant',
        ]);

        $response = $this->postJson(
            "/api/webhooks/payments/{$tenant->id}",
            json_decode($payload, true),
            ['Fincode-Signature' => $this->generateSignature($payload)]
        );

        $response->assertStatus(200)->assertJson(['status' => 'accepted']);

        $this->assertDatabaseMissing('webhook_logs', [
            'fincode_id' => 'pay_shop_mismatch',
        ]);
        Queue::assertNotPushed(ProcessPaymentWebhookJob::class);
    }

    #[Test]
    public function it_processes_payment_refunded_event(): void
    {
        Queue::fake();

        $tenant = $this->createTenantWithShop();

        $payload = $this->makePayload([
            'event' => 'payment.refunded',
            'id' => 'pay_refund_test',
            'refund_amount' => 500,
        ]);

        $response = $this->postJson(
            "/api/webhooks/payments/{$tenant->id}",
            json_decode($payload, true),
            [
                'Fincode-Signature' => $this->generateSignature($payload),
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('webhook_logs', [
            'event_type' => 'payment.refunded',
            'fincode_id' => 'pay_refund_test',
        ]);

        Queue::assertPushed(ProcessPaymentWebhookJob::class);
    }
}

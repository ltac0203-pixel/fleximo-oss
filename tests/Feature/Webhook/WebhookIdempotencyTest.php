<?php

declare(strict_types=1);

namespace Tests\Feature\Webhook;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\ProcessPaymentWebhookJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookLog;
use App\Services\Webhook\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'test-webhook-secret';

    private const TEST_SHOP_ID = 'shop_test_12345';

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('fincode.webhook_secret', $this->webhookSecret);
        $this->tenant = Tenant::factory()->create(['fincode_shop_id' => self::TEST_SHOP_ID]);
    }

    private function generateSignature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->webhookSecret);
    }

    private function sendWebhook(array $data): \Illuminate\Testing\TestResponse
    {
        return $this->sendWebhookToTenant($this->tenant, $data);
    }

    private function sendWebhookToTenant(Tenant $tenant, array $data): \Illuminate\Testing\TestResponse
    {
        // 新規テナントが fincode_shop_id 未設定の場合、payload 側もテナント側と一致するよう調整する
        $data['shop_id'] = $data['shop_id'] ?? ($tenant->fincode_shop_id ?? self::TEST_SHOP_ID);
        $data['created'] = $data['created'] ?? now()->toIso8601String();
        $payload = json_encode($data);

        return $this->postJson(
            "/api/webhooks/payments/{$tenant->id}",
            $data,
            [
                'Fincode-Signature' => $this->generateSignature($payload),
                'Content-Type' => 'application/json',
            ]
        );
    }

    public function test_duplicate_webhook_is_ignored(): void
    {
        Queue::fake();

        $fincodeId = 'pay_dup_test_123';

        // 1回目の送信
        $response1 = $this->sendWebhook([
            'event' => 'payment.completed',
            'id' => $fincodeId,
            'amount' => 1000,
        ]);

        $response1->assertOk()
            ->assertJson(['status' => 'accepted']);

        Queue::assertPushed(ProcessPaymentWebhookJob::class, 1);

        // 2回目の送信（重複）
        $response2 = $this->sendWebhook([
            'event' => 'payment.completed',
            'id' => $fincodeId,
            'amount' => 1000,
        ]);

        $response2->assertOk()
            ->assertJson([
                'status' => 'accepted',
            ]);

        // ジョブは1回しか発行されない
        Queue::assertPushed(ProcessPaymentWebhookJob::class, 1);
    }

    public function test_duplicate_check_uses_fincode_id(): void
    {
        Queue::fake();

        // 同じイベントでも異なるfincode_idなら別扱い
        $this->sendWebhook([
            'event' => 'payment.completed',
            'id' => 'pay_first',
        ]);

        $this->sendWebhook([
            'event' => 'payment.completed',
            'id' => 'pay_second',
        ]);

        Queue::assertPushed(ProcessPaymentWebhookJob::class, 2);
        $this->assertCount(2, WebhookLog::all());
    }

    public function test_same_fincode_id_with_different_events_is_accepted(): void
    {
        Queue::fake();

        $fincodeId = 'paypay_event_chain_123';

        $response1 = $this->sendWebhook([
            'event' => 'payments.paypay.regis',
            'id' => $fincodeId,
            'status' => 'AWAITING_CUSTOMER_PAYMENT',
        ]);

        $response1->assertOk()
            ->assertJson(['status' => 'accepted']);

        $response2 = $this->sendWebhook([
            'event' => 'payments.paypay.capture',
            'id' => $fincodeId,
            'status' => 'CAPTURED',
        ]);

        $response2->assertOk()
            ->assertJson(['status' => 'accepted']);

        Queue::assertPushed(ProcessPaymentWebhookJob::class, 2);
        $this->assertDatabaseHas('webhook_logs', [
            'fincode_id' => $fincodeId,
            'event_type' => 'payments.paypay.regis',
        ]);
        $this->assertDatabaseHas('webhook_logs', [
            'fincode_id' => $fincodeId,
            'event_type' => 'payments.paypay.capture',
        ]);
    }

    public function test_same_fincode_id_for_different_tenants_is_not_treated_as_duplicate(): void
    {
        Queue::fake();

        $otherTenant = Tenant::factory()->create(['fincode_shop_id' => 'shop_other_test']);
        $fincodeId = 'pay_cross_tenant_123';

        $this->sendWebhookToTenant($this->tenant, [
            'event' => 'payment.completed',
            'id' => $fincodeId,
            'amount' => 1000,
        ]);

        $this->sendWebhookToTenant($otherTenant, [
            'event' => 'payment.completed',
            'id' => $fincodeId,
            'amount' => 1000,
        ]);

        Queue::assertPushed(ProcessPaymentWebhookJob::class, 2);
        $this->assertDatabaseHas('webhook_logs', [
            'tenant_id' => $this->tenant->id,
            'fincode_id' => $fincodeId,
            'event_type' => 'payment.completed',
        ]);
        $this->assertDatabaseHas('webhook_logs', [
            'tenant_id' => $otherTenant->id,
            'fincode_id' => $fincodeId,
            'event_type' => 'payment.completed',
        ]);
    }

    public function test_null_fincode_id_is_not_treated_as_duplicate(): void
    {
        Queue::fake();

        // fincode_idがnullのwebhookは重複チェックされない
        $this->sendWebhook([
            'event' => 'payment.completed',
        ]);

        $this->sendWebhook([
            'event' => 'payment.completed',
        ]);

        Queue::assertPushed(ProcessPaymentWebhookJob::class, 2);
    }

    public function test_invalid_signature_returns_uniform_response(): void
    {
        $response = $this->postJson(
            "/api/webhooks/payments/{$this->tenant->id}",
            ['event' => 'payment.completed', 'id' => 'pay_test'],
            ['Fincode-Signature' => 'invalid-signature-value']
        );

        $response->assertOk()
            ->assertJson(['status' => 'accepted']);

        $this->assertDatabaseMissing('webhook_logs', [
            'fincode_id' => 'pay_test',
        ]);
    }

    public function test_missing_signature_returns_uniform_response(): void
    {
        $response = $this->postJson(
            "/api/webhooks/payments/{$this->tenant->id}",
            ['event' => 'payment.completed', 'id' => 'pay_test']
        );

        $response->assertOk()
            ->assertJson(['status' => 'accepted']);
    }

    public function test_webhook_secret_not_configured_returns_uniform_response(): void
    {
        Config::set('fincode.webhook_secret', null);

        $response = $this->postJson(
            "/api/webhooks/payments/{$this->tenant->id}",
            ['event' => 'payment.completed', 'id' => 'pay_test']
        );

        $response->assertOk()
            ->assertJson(['status' => 'accepted']);
    }

    public function test_webhook_processing_updates_payment_and_order(): void
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $customer->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'status' => PaymentStatus::Processing,
            'fincode_id' => 'pay_process_test',
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_process_test',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_process_test', 'event' => 'payment.completed'],
        ]);

        // WebhookServiceで直接処理
        $webhookService = app(WebhookService::class);
        $webhookService->processEvent($webhookLog);

        $payment->refresh();
        $order->refresh();

        $this->assertEquals(PaymentStatus::Completed, $payment->status);
        $this->assertEquals(OrderStatus::Accepted, $order->status);
        $this->assertTrue($webhookLog->fresh()->processed);
    }

    public function test_webhook_failed_event_marks_payment_and_order_failed(): void
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $customer->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'status' => PaymentStatus::Pending,
            'fincode_id' => 'pay_fail_test',
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_fail_test',
            'event_type' => 'payment.failed',
            'payload' => ['id' => 'pay_fail_test', 'event' => 'payment.failed'],
        ]);

        $webhookService = app(WebhookService::class);
        $webhookService->processEvent($webhookLog);

        $payment->refresh();
        $order->refresh();

        $this->assertEquals(PaymentStatus::Failed, $payment->status);
        $this->assertEquals(OrderStatus::PaymentFailed, $order->status);
    }

    public function test_webhook_processing_scopes_payment_lookup_to_log_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $customerA = User::factory()->customer()->create();
        $customerB = User::factory()->customer()->create();
        $sharedFincodeId = 'pay_shared_external_id';

        $orderA = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $customerA->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $paymentA = Payment::factory()->create([
            'order_id' => $orderA->id,
            'tenant_id' => $this->tenant->id,
            'status' => PaymentStatus::Processing,
            'fincode_id' => $sharedFincodeId,
        ]);

        $orderB = Order::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $customerB->id,
            'status' => OrderStatus::PendingPayment,
        ]);
        $paymentB = Payment::factory()->create([
            'order_id' => $orderB->id,
            'tenant_id' => $otherTenant->id,
            'status' => PaymentStatus::Processing,
            'fincode_id' => $sharedFincodeId,
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $otherTenant->id,
            'provider' => 'fincode',
            'fincode_id' => $sharedFincodeId,
            'event_type' => 'payment.completed',
            'payload' => ['id' => $sharedFincodeId, 'event' => 'payment.completed'],
        ]);

        app(WebhookService::class)->processEvent($webhookLog);

        $this->assertEquals(PaymentStatus::Processing, $paymentA->fresh()->status);
        $this->assertEquals(OrderStatus::PendingPayment, $orderA->fresh()->status);
        $this->assertEquals(PaymentStatus::Completed, $paymentB->fresh()->status);
        $this->assertEquals(OrderStatus::Accepted, $orderB->fresh()->status);
    }

    public function test_already_completed_payment_not_reprocessed(): void
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $customer->id,
            'status' => OrderStatus::Accepted,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'tenant_id' => $this->tenant->id,
            'status' => PaymentStatus::Completed,
            'fincode_id' => 'pay_already_done',
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_already_done',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_already_done', 'event' => 'payment.completed'],
        ]);

        $webhookService = app(WebhookService::class);
        $webhookService->processEvent($webhookLog);

        // ステータスは変わらない
        $this->assertEquals(PaymentStatus::Completed, $payment->fresh()->status);
        $this->assertEquals(OrderStatus::Accepted, $order->fresh()->status);
    }

    public function test_job_retries_configuration(): void
    {
        $webhookLog = WebhookLog::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $job = new ProcessPaymentWebhookJob($webhookLog);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([60, 300, 900], $job->backoff);
        $this->assertEquals('webhook_'.$webhookLog->id, $job->uniqueId());
    }
}

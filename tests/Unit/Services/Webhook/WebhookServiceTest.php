<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Webhook;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentNotFoundException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookLog;
use App\Services\Webhook\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    private WebhookService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WebhookService;
    }

    #[Test]
    public function test_creates_webhook_log(): void
    {
        $tenant = Tenant::factory()->create();

        $result = $this->service->findOrCreateLog(
            $tenant->id,
            'payment.completed',
            ['id' => 'pay_123', 'event' => 'payment.completed'],
            'pay_123'
        );

        $this->assertFalse($result['is_duplicate']);

        $this->assertDatabaseHas('webhook_logs', [
            'id' => $result['log']->id,
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_123',
            'event_type' => 'payment.completed',
            'processed' => false,
        ]);
    }

    #[Test]
    public function test_detects_duplicate_webhook(): void
    {
        $tenant = Tenant::factory()->create();

        $this->service->findOrCreateLog(
            $tenant->id,
            'payment.completed',
            ['id' => 'pay_123'],
            'pay_123'
        );

        $result = $this->service->findOrCreateLog(
            $tenant->id,
            'payment.completed',
            ['id' => 'pay_123'],
            'pay_123'
        );
        $this->assertTrue($result['is_duplicate']);

        $result2 = $this->service->findOrCreateLog(
            $tenant->id,
            'payment.completed',
            ['id' => 'pay_456'],
            'pay_456'
        );
        $this->assertFalse($result2['is_duplicate']);

        $result3 = $this->service->findOrCreateLog(
            $tenant->id,
            'payment.completed',
            ['id' => 'pay_null'],
            null
        );
        $this->assertFalse($result3['is_duplicate']);
    }

    #[Test]
    public function test_allows_same_fincode_id_when_event_type_differs(): void
    {
        $tenant = Tenant::factory()->create();

        $first = $this->service->findOrCreateLog(
            $tenant->id,
            'payments.paypay.regis',
            ['id' => 'pay_shared_123', 'event' => 'payments.paypay.regis'],
            'pay_shared_123'
        );
        $second = $this->service->findOrCreateLog(
            $tenant->id,
            'payments.paypay.capture',
            ['id' => 'pay_shared_123', 'event' => 'payments.paypay.capture'],
            'pay_shared_123'
        );

        $this->assertFalse($first['is_duplicate']);
        $this->assertFalse($second['is_duplicate']);
        $this->assertCount(2, WebhookLog::where('fincode_id', 'pay_shared_123')->get());
    }

    #[Test]
    public function test_checks_event_support(): void
    {
        $this->assertTrue($this->service->isEventSupported('payment.completed'));
        $this->assertTrue($this->service->isEventSupported('payment.failed'));
        $this->assertTrue($this->service->isEventSupported('payment.refunded'));
        $this->assertFalse($this->service->isEventSupported('payment.unknown'));
    }

    #[Test]
    public function test_processes_payment_completed_event(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
        ]);

        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'fincode_id' => 'pay_completed_123',
            'status' => PaymentStatus::Processing,
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_completed_123',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_completed_123', 'event' => 'payment.completed'],
        ]);

        $this->service->processEvent($webhookLog);

        $payment->refresh();
        $order->refresh();
        $webhookLog->refresh();

        $this->assertEquals(PaymentStatus::Completed, $payment->status);
        $this->assertEquals(OrderStatus::Accepted, $order->status);
        $this->assertTrue($webhookLog->processed);
        $this->assertNotNull($webhookLog->processed_at);
    }

    #[Test]
    public function test_resolves_payment_by_payload_id_before_order_id(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
        ]);

        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'fincode_id' => 'pay_id_priority_123',
            'status' => PaymentStatus::Processing,
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'wrong_order_reference_999',
            'event_type' => 'payment.completed',
            'payload' => [
                'id' => 'pay_id_priority_123',
                'order_id' => 'wrong_order_reference_999',
                'event' => 'payment.completed',
            ],
        ]);

        $this->service->processEvent($webhookLog);

        $payment->refresh();
        $order->refresh();
        $webhookLog->refresh();

        $this->assertEquals(PaymentStatus::Completed, $payment->status);
        $this->assertEquals(OrderStatus::Accepted, $order->status);
        $this->assertTrue($webhookLog->processed);
    }

    #[Test]
    public function test_falls_back_to_order_id_when_payload_id_does_not_match_payment(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
        ]);

        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'fincode_id' => 'pay_order_fallback_123',
            'status' => PaymentStatus::Processing,
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'unknown_fincode_id',
            'event_type' => 'payment.completed',
            'payload' => [
                'id' => 'unknown_fincode_id',
                'order_id' => 'pay_order_fallback_123',
                'event' => 'payment.completed',
            ],
        ]);

        $this->service->processEvent($webhookLog);

        $payment->refresh();
        $order->refresh();
        $webhookLog->refresh();

        $this->assertEquals(PaymentStatus::Completed, $payment->status);
        $this->assertEquals(OrderStatus::Accepted, $order->status);
        $this->assertTrue($webhookLog->processed);
    }

    #[Test]
    public function test_processes_payment_failed_event(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
        ]);

        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'fincode_id' => 'pay_failed_123',
            'status' => PaymentStatus::Processing,
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_failed_123',
            'event_type' => 'payment.failed',
            'payload' => ['id' => 'pay_failed_123', 'event' => 'payment.failed'],
        ]);

        $this->service->processEvent($webhookLog);

        $payment->refresh();
        $order->refresh();
        $webhookLog->refresh();

        $this->assertEquals(PaymentStatus::Failed, $payment->status);
        $this->assertEquals(OrderStatus::PaymentFailed, $order->status);
        $this->assertTrue($webhookLog->processed);
    }

    #[Test]
    public function test_processes_payment_refunded_event(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::Cancelled,
        ]);

        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'fincode_id' => 'pay_refunded_123',
            'status' => PaymentStatus::Completed,
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_refunded_123',
            'event_type' => 'payment.refunded',
            'payload' => ['id' => 'pay_refunded_123', 'event' => 'payment.refunded'],
        ]);

        $this->service->processEvent($webhookLog);

        $order->refresh();
        $webhookLog->refresh();

        $this->assertEquals(OrderStatus::Refunded, $order->status);
        $this->assertTrue($webhookLog->processed);
    }

    #[Test]
    public function test_throws_exception_when_payment_not_found(): void
    {
        $tenant = Tenant::factory()->create();

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_not_found_123',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_not_found_123', 'event' => 'payment.completed'],
        ]);

        $this->expectException(PaymentNotFoundException::class);

        $this->service->processEvent($webhookLog);
    }

    #[Test]
    public function test_marks_unsupported_event_as_processed(): void
    {
        $tenant = Tenant::factory()->create();

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_unknown_123',
            'event_type' => 'payment.unknown',
            'payload' => ['id' => 'pay_unknown_123', 'event' => 'payment.unknown'],
        ]);

        $this->service->processEvent($webhookLog);

        $webhookLog->refresh();
        $this->assertTrue($webhookLog->processed);
    }

    #[Test]
    public function test_processes_paypay_captured_event(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
        ]);

        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'fincode_id' => 'paypay_captured_123',
            'status' => PaymentStatus::Processing,
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'paypay_captured_123',
            'event_type' => 'payments.paypay.capture',
            'payload' => ['order_id' => 'paypay_captured_123', 'event' => 'payments.paypay.capture', 'status' => 'CAPTURED'],
        ]);

        $this->service->processEvent($webhookLog);

        $payment->refresh();
        $order->refresh();
        $webhookLog->refresh();

        $this->assertEquals(PaymentStatus::Completed, $payment->status);
        $this->assertEquals(OrderStatus::Accepted, $order->status);
        $this->assertTrue($webhookLog->processed);
    }

    #[Test]
    public function test_processes_paypay_canceled_event(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
        ]);

        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'fincode_id' => 'paypay_canceled_123',
            'status' => PaymentStatus::Processing,
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'paypay_canceled_123',
            'event_type' => 'payments.paypay.exec',
            'payload' => ['order_id' => 'paypay_canceled_123', 'event' => 'payments.paypay.exec', 'status' => 'CANCELED'],
        ]);

        $this->service->processEvent($webhookLog);

        $payment->refresh();
        $order->refresh();
        $webhookLog->refresh();

        $this->assertEquals(PaymentStatus::Failed, $payment->status);
        $this->assertEquals(OrderStatus::PaymentFailed, $order->status);
        $this->assertTrue($webhookLog->processed);
    }

    #[Test]
    public function test_processes_paypay_intermediate_status(): void
    {
        $tenant = Tenant::factory()->create();

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'paypay_awaiting_123',
            'event_type' => 'payments.paypay.regis',
            'payload' => ['order_id' => 'paypay_awaiting_123', 'event' => 'payments.paypay.regis', 'status' => 'AWAITING_CUSTOMER_PAYMENT'],
        ]);

        $this->service->processEvent($webhookLog);

        $webhookLog->refresh();
        $this->assertTrue($webhookLog->processed);
    }

    #[Test]
    public function test_supports_paypay_events(): void
    {
        $this->assertTrue($this->service->isEventSupported('payments.paypay.regis'));
        $this->assertTrue($this->service->isEventSupported('payments.paypay.exec'));
        $this->assertTrue($this->service->isEventSupported('payments.paypay.capture'));
        $this->assertTrue($this->service->isEventSupported('payments.paypay.complete'));
    }

    #[Test]
    public function test_handles_order_already_accepted_by_confirm_flow(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);

        // confirm フローが先に Order を Accepted まで遷移済み
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::Accepted,
        ]);

        // Payment は Processing のまま（Webhook が先に到着するケース）
        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'fincode_id' => 'pay_race_condition',
            'status' => PaymentStatus::Processing,
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_race_condition',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_race_condition', 'event' => 'payment.completed'],
        ]);

        // 例外なく処理完了することを確認
        $this->service->processEvent($webhookLog);

        $payment->refresh();
        $order->refresh();
        $webhookLog->refresh();

        $this->assertEquals(PaymentStatus::Completed, $payment->status);
        $this->assertEquals(OrderStatus::Accepted, $order->status);
        $this->assertTrue($webhookLog->processed);
    }

    #[Test]
    public function test_does_not_update_already_completed_payment(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => OrderStatus::Accepted,
        ]);

        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'fincode_id' => 'pay_already_completed',
            'status' => PaymentStatus::Completed,
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_already_completed',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_already_completed', 'event' => 'payment.completed'],
        ]);

        $this->service->processEvent($webhookLog);

        $payment->refresh();
        $order->refresh();
        $webhookLog->refresh();

        $this->assertEquals(PaymentStatus::Completed, $payment->status);
        $this->assertEquals(OrderStatus::Accepted, $order->status);
        $this->assertTrue($webhookLog->processed);
    }
}

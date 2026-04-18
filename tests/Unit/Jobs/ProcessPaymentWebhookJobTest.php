<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentNotFoundException;
use App\Jobs\ProcessPaymentWebhookJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookLog;
use App\Services\Webhook\WebhookService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessPaymentWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_processes_webhook_successfully(): void
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
            'fincode_id' => 'pay_job_123',
            'status' => PaymentStatus::Processing,
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_job_123',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_job_123', 'event' => 'payment.completed'],
        ]);

        $job = new ProcessPaymentWebhookJob($webhookLog);
        $job->handle(new \App\Services\Webhook\WebhookService);

        $payment->refresh();
        $order->refresh();
        $webhookLog->refresh();

        $this->assertEquals(PaymentStatus::Completed, $payment->status);
        $this->assertEquals(OrderStatus::Accepted, $order->status);
        $this->assertTrue($webhookLog->processed);
    }

    #[Test]
    public function it_marks_webhook_as_failed_when_payment_not_found(): void
    {
        $tenant = Tenant::factory()->create();

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_not_found_job',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_not_found_job', 'event' => 'payment.completed'],
        ]);

        $job = new ProcessPaymentWebhookJob($webhookLog);

        try {
            $job->handle(new \App\Services\Webhook\WebhookService);
        } catch (PaymentNotFoundException $e) {

        }

        $webhookLog->refresh();

        $this->assertFalse($webhookLog->processed);
        $this->assertNotNull($webhookLog->error_message);
    }

    #[Test]
    public function it_has_correct_retry_settings(): void
    {
        $tenant = Tenant::factory()->create();

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_retry_test',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_retry_test'],
        ]);

        $job = new ProcessPaymentWebhookJob($webhookLog);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([60, 300, 900], $job->backoff);
    }

    #[Test]
    public function it_has_unique_id(): void
    {
        $tenant = Tenant::factory()->create();

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_unique_123',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_unique_123'],
        ]);

        $job = new ProcessPaymentWebhookJob($webhookLog);

        $this->assertEquals('webhook_'.$webhookLog->id, $job->uniqueId());
    }

    #[Test]
    public function it_has_correct_tags(): void
    {
        $tenant = Tenant::factory()->create();

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_tags_123',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_tags_123'],
        ]);

        $job = new ProcessPaymentWebhookJob($webhookLog);
        $tags = $job->tags();

        $this->assertContains('webhook', $tags);
        $this->assertContains('payment', $tags);
        $this->assertContains('webhook_log:'.$webhookLog->id, $tags);
        $this->assertContains('tenant:'.$tenant->id, $tags);
    }

    #[Test]
    public function it_can_be_dispatched(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_dispatch_123',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_dispatch_123'],
        ]);

        ProcessPaymentWebhookJob::dispatch($webhookLog);

        Queue::assertPushed(ProcessPaymentWebhookJob::class, function ($job) use ($webhookLog) {
            return $job->webhookLog->id === $webhookLog->id;
        });
    }

    #[Test]
    public function it_marks_as_failed_immediately_on_invalid_state_transition(): void
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
            'fincode_id' => 'pay_invalid_transition',
            'status' => PaymentStatus::Failed,
        ]);

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_invalid_transition',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_invalid_transition', 'event' => 'payment.completed'],
        ]);

        $job = new ProcessPaymentWebhookJob($webhookLog);

        try {
            $job->handle(new \App\Services\Webhook\WebhookService);
        } catch (\InvalidArgumentException $e) {
            // fail() は例外を投げるため想定どおり
        }

        $webhookLog->refresh();

        $this->assertNotNull($webhookLog->error_message);
        $this->assertFalse($webhookLog->processed);
    }

    #[Test]
    public function it_rethrows_query_exception_for_retry(): void
    {
        $tenant = Tenant::factory()->create();

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_db_error_test',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_db_error_test'],
        ]);

        $queryException = new QueryException(
            'mysql',
            'SELECT * FROM payments WHERE id = ?',
            [1],
            new \Exception('Deadlock found')
        );

        $mockService = $this->createMock(WebhookService::class);
        $mockService->method('processEvent')
            ->willThrowException($queryException);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($webhookLog) {
                return $message === 'DB error in webhook processing'
                    && $context['webhook_log_id'] === $webhookLog->id
                    && $context['event_type'] === 'payment.completed'
                    && str_contains($context['error'], 'Deadlock found');
            });

        $job = new ProcessPaymentWebhookJob($webhookLog);

        $this->expectException(QueryException::class);
        $job->handle($mockService);
    }

    #[Test]
    public function it_records_error_on_failure(): void
    {
        $tenant = Tenant::factory()->create();

        $webhookLog = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'provider' => 'fincode',
            'fincode_id' => 'pay_failure_test',
            'event_type' => 'payment.completed',
            'payload' => ['id' => 'pay_failure_test'],
        ]);

        $job = new ProcessPaymentWebhookJob($webhookLog);
        $exception = new \Exception('Test error');

        $job->failed($exception);

        $webhookLog->refresh();

        $this->assertFalse($webhookLog->processed);
        $this->assertEquals('Test error', $webhookLog->error_message);
    }
}

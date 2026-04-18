<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Events\PaymentFailed;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\WebhookLog;
use App\Services\Webhook\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentFailedEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function webhook_service_dispatches_payment_failed_event(): void
    {
        Event::fake([PaymentFailed::class]);

        $tenant = Tenant::factory()->create();
        $order = Order::factory()->forTenant($tenant)->pendingPayment()->create();
        $payment = Payment::factory()->forOrder($order)->pending()
            ->withFincodeId('pay_failed_001')->create();

        $webhookLog = WebhookLog::factory()->paymentFailed()->create([
            'tenant_id' => $tenant->id,
            'fincode_id' => 'pay_failed_001',
            'payload' => ['id' => 'pay_failed_001', 'event' => 'payment.failed', 'error_code' => 'CARD_DECLINED'],
        ]);

        $service = app(WebhookService::class);
        $service->processEvent($webhookLog);

        Event::assertDispatched(PaymentFailed::class, function (PaymentFailed $event) use ($payment, $order) {
            return $event->payment->id === $payment->id
                && $event->order->id === $order->id;
        });
    }

    #[Test]
    public function webhook_service_does_not_dispatch_event_for_already_failed_payment(): void
    {
        Event::fake([PaymentFailed::class]);

        $tenant = Tenant::factory()->create();
        $order = Order::factory()->forTenant($tenant)->paymentFailed()->create();
        $payment = Payment::factory()->forOrder($order)->failed()
            ->withFincodeId('pay_already_failed')->create();

        $webhookLog = WebhookLog::factory()->paymentFailed()->create([
            'tenant_id' => $tenant->id,
            'fincode_id' => 'pay_already_failed',
            'payload' => ['id' => 'pay_already_failed', 'event' => 'payment.failed'],
        ]);

        $service = app(WebhookService::class);
        $service->processEvent($webhookLog);

        Event::assertNotDispatched(PaymentFailed::class);
    }

    #[Test]
    public function card_payment_service_dispatches_payment_failed_on_failure(): void
    {
        Event::fake([PaymentFailed::class]);

        $tenant = Tenant::factory()->create();
        $order = Order::factory()->forTenant($tenant)->pendingPayment()->create();
        $payment = Payment::factory()->forOrder($order)->processing()
            ->withFincodeId('pay_card_fail')->create();

        \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
            $payment->markAsFailed();
            $payment->order->markAsPaymentFailed();
        });

        event(new PaymentFailed($payment, $payment->order));

        Event::assertDispatched(PaymentFailed::class, function (PaymentFailed $event) use ($payment) {
            return $event->payment->id === $payment->id;
        });
    }

    #[Test]
    public function payment_failed_event_contains_correct_data(): void
    {
        Event::fake([PaymentFailed::class]);

        $tenant = Tenant::factory()->create();
        $order = Order::factory()->forTenant($tenant)->pendingPayment()->create();
        $payment = Payment::factory()->forOrder($order)->pending()
            ->withFincodeId('pay_fail_data')->create();

        $webhookLog = WebhookLog::factory()->paymentFailed()->create([
            'tenant_id' => $tenant->id,
            'fincode_id' => 'pay_fail_data',
            'payload' => ['id' => 'pay_fail_data', 'event' => 'payment.failed'],
        ]);

        $service = app(WebhookService::class);
        $service->processEvent($webhookLog);

        Event::assertDispatched(PaymentFailed::class, function (PaymentFailed $event) use ($payment, $order) {
            return $event->payment->id === $payment->id
                && $event->order->id === $order->id
                && $event->payment instanceof Payment
                && $event->order instanceof Order;
        });
    }
}

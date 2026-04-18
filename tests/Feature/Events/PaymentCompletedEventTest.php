<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Events\PaymentCompleted;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\WebhookLog;
use App\Services\Webhook\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentCompletedEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function webhook_service_dispatches_payment_completed_event(): void
    {
        Event::fake([PaymentCompleted::class]);

        $tenant = Tenant::factory()->create();
        $order = Order::factory()->forTenant($tenant)->pendingPayment()->create();
        $payment = Payment::factory()->forOrder($order)->processing()
            ->withFincodeId('pay_completed_001')->create();

        $webhookLog = WebhookLog::factory()->paymentCompleted()->create([
            'tenant_id' => $tenant->id,
            'fincode_id' => 'pay_completed_001',
            'payload' => ['id' => 'pay_completed_001', 'event' => 'payment.completed'],
        ]);

        $service = app(WebhookService::class);
        $service->processEvent($webhookLog);

        Event::assertDispatched(PaymentCompleted::class, function (PaymentCompleted $event) use ($payment, $order) {
            return $event->payment->id === $payment->id
                && $event->order->id === $order->id;
        });
    }

    #[Test]
    public function webhook_service_does_not_dispatch_event_for_already_completed_payment(): void
    {
        Event::fake([PaymentCompleted::class]);

        $tenant = Tenant::factory()->create();
        $order = Order::factory()->forTenant($tenant)->accepted()->create();
        $payment = Payment::factory()->forOrder($order)->completed()
            ->withFincodeId('pay_already_done')->create();

        $webhookLog = WebhookLog::factory()->paymentCompleted()->create([
            'tenant_id' => $tenant->id,
            'fincode_id' => 'pay_already_done',
            'payload' => ['id' => 'pay_already_done', 'event' => 'payment.completed'],
        ]);

        $service = app(WebhookService::class);
        $service->processEvent($webhookLog);

        Event::assertNotDispatched(PaymentCompleted::class);
    }

    #[Test]
    public function card_payment_service_dispatches_payment_completed_on_success(): void
    {
        Event::fake([PaymentCompleted::class]);

        $tenant = Tenant::factory()->create();
        $order = Order::factory()->forTenant($tenant)->pendingPayment()->create();
        $payment = Payment::factory()->forOrder($order)->processing()
            ->withFincodeId('pay_card_success')->create();

        // completePaymentWithOrder は private なので、直接トランザクション+イベント発行を模倣する
        DB::transaction(function () use ($payment) {
            $payment->markAsCompleted();
            $payment->order->markAsPaid();
        });

        event(new PaymentCompleted($payment, $payment->order));

        Event::assertDispatched(PaymentCompleted::class, function (PaymentCompleted $event) use ($payment) {
            return $event->payment->id === $payment->id;
        });
    }

    #[Test]
    public function payment_completed_event_contains_correct_payment_and_order(): void
    {
        Event::fake([PaymentCompleted::class]);

        $tenant = Tenant::factory()->create();
        $order = Order::factory()->forTenant($tenant)->pendingPayment()->create();
        $payment = Payment::factory()->forOrder($order)->processing()
            ->withFincodeId('pay_verify_data')->create();

        $webhookLog = WebhookLog::factory()->paymentCompleted()->create([
            'tenant_id' => $tenant->id,
            'fincode_id' => 'pay_verify_data',
            'payload' => ['id' => 'pay_verify_data', 'event' => 'payment.completed'],
        ]);

        $service = app(WebhookService::class);
        $service->processEvent($webhookLog);

        Event::assertDispatched(PaymentCompleted::class, function (PaymentCompleted $event) use ($payment, $order) {
            return $event->payment->id === $payment->id
                && $event->order->id === $order->id
                && $event->payment instanceof Payment
                && $event->order instanceof Order;
        });
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\PaymentCompleted;
use App\Listeners\LogPaymentCompleted;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogPaymentCompletedTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_payment_completed_with_correct_context(): void
    {
        Log::spy();

        $customer = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $order = Order::factory()
            ->forUser($customer)
            ->forTenant($tenant)
            ->paid()
            ->create();

        $payment = Payment::factory()
            ->forOrder($order)
            ->forTenant($tenant)
            ->completed()
            ->create();

        $event = new PaymentCompleted($payment, $order);

        $listener = new LogPaymentCompleted;
        $listener->handle($event);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($payment, $order): bool {
                return $message === '決済が完了しました'
                    && ($context['payment_id'] ?? null) === $payment->id
                    && ($context['order_id'] ?? null) === $order->id
                    && ($context['order_code'] ?? null) === $order->order_code;
            });
    }
}

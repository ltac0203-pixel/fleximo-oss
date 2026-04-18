<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\PaymentFailed;
use App\Listeners\LogPaymentFailed;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogPaymentFailedTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_payment_failed_with_reason(): void
    {
        Log::spy();

        $customer = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $order = Order::factory()
            ->forUser($customer)
            ->forTenant($tenant)
            ->pendingPayment()
            ->create();

        $payment = Payment::factory()
            ->forOrder($order)
            ->forTenant($tenant)
            ->failed()
            ->create();

        $event = new PaymentFailed($payment, $order, 'カード残高不足');

        $listener = new LogPaymentFailed;
        $listener->handle($event);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($payment, $order): bool {
                return $message === '決済が失敗しました'
                    && ($context['payment_id'] ?? null) === $payment->id
                    && ($context['order_id'] ?? null) === $order->id
                    && ($context['reason'] ?? null) === 'カード残高不足';
            });
    }

    public function test_logs_payment_failed_with_null_reason(): void
    {
        Log::spy();

        $customer = User::factory()->customer()->create();
        $tenant = Tenant::factory()->create();

        $order = Order::factory()
            ->forUser($customer)
            ->forTenant($tenant)
            ->pendingPayment()
            ->create();

        $payment = Payment::factory()
            ->forOrder($order)
            ->forTenant($tenant)
            ->failed()
            ->create();

        $event = new PaymentFailed($payment, $order);

        $listener = new LogPaymentFailed;
        $listener->handle($event);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($payment, $order): bool {
                return $message === '決済が失敗しました'
                    && ($context['payment_id'] ?? null) === $payment->id
                    && ($context['order_id'] ?? null) === $order->id
                    && ($context['reason'] ?? null) === null;
            });
    }
}

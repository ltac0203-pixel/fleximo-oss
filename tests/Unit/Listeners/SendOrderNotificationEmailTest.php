<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Listeners\SendOrderNotificationEmail;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderCompletedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendOrderNotificationEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->customer = User::factory()->customer()->create();
        $this->tenant = Tenant::factory()->create();
    }

    public function test_completed_status_queues_order_completed_mail(): void
    {
        Log::spy();

        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->completed()
            ->create();

        OrderItem::factory()->forOrder($order)->create();

        $event = new OrderStatusChanged($order, OrderStatus::Ready, OrderStatus::Completed);

        $listener = new SendOrderNotificationEmail;
        $listener->handle($event);

        Mail::assertQueued(OrderCompletedMail::class, function (OrderCompletedMail $mail) use ($order) {
            return $mail->order->id === $order->id
                && $mail->hasTo($this->customer->email);
        });

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($order): bool {
                return $message === '注文通知メールをキューに追加しました'
                    && ($context['order_id'] ?? null) === $order->id
                    && ($context['order_code'] ?? null) === $order->order_code
                    && ($context['status'] ?? null) === OrderStatus::Completed->value
                    && ! array_key_exists('email', $context);
            });
    }

    public function test_cancelled_status_queues_order_cancelled_mail(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->cancelled()
            ->create();

        OrderItem::factory()->forOrder($order)->create();

        $event = new OrderStatusChanged($order, OrderStatus::Accepted, OrderStatus::Cancelled);

        $listener = new SendOrderNotificationEmail;
        $listener->handle($event);

        Mail::assertQueued(OrderCancelledMail::class, function (OrderCancelledMail $mail) use ($order) {
            return $mail->order->id === $order->id
                && $mail->hasTo($this->customer->email);
        });
    }

    public function test_other_status_changes_do_not_send_mail(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->accepted()
            ->create();

        $event = new OrderStatusChanged($order, OrderStatus::Paid, OrderStatus::Accepted);

        $listener = new SendOrderNotificationEmail;
        $listener->handle($event);

        Mail::assertNothingQueued();
    }

    public function test_in_progress_status_does_not_send_mail(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->inProgress()
            ->create();

        $event = new OrderStatusChanged($order, OrderStatus::Accepted, OrderStatus::InProgress);

        $listener = new SendOrderNotificationEmail;
        $listener->handle($event);

        Mail::assertNothingQueued();
    }

    public function test_ready_status_does_not_send_mail(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->ready()
            ->create();

        $event = new OrderStatusChanged($order, OrderStatus::InProgress, OrderStatus::Ready);

        $listener = new SendOrderNotificationEmail;
        $listener->handle($event);

        Mail::assertNothingQueued();
    }

    public function test_skips_when_user_is_deleted(): void
    {
        $deletedUser = User::factory()->customer()->create();

        $order = Order::factory()
            ->forUser($deletedUser)
            ->forTenant($this->tenant)
            ->completed()
            ->create();

        // ユーザー削除後にリスナーが実行される状況をシミュレート
        $deletedUser->delete();
        $order->unsetRelation('user');

        $event = new OrderStatusChanged($order, OrderStatus::Ready, OrderStatus::Completed);

        $listener = new SendOrderNotificationEmail;
        $listener->handle($event);

        Mail::assertNothingQueued();
    }

    public function test_mail_queue_failure_log_excludes_email_address(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->completed()
            ->create();

        OrderItem::factory()->forOrder($order)->create();

        Mail::shouldReceive('to')
            ->once()
            ->with($this->customer->email)
            ->andThrow(new \RuntimeException('Queue unavailable'));

        Log::spy();

        $event = new OrderStatusChanged($order, OrderStatus::Ready, OrderStatus::Completed);

        $listener = new SendOrderNotificationEmail;
        $listener->handle($event);

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context) use ($order): bool {
                return $message === '注文通知メールのキュー追加に失敗しました'
                    && ($context['order_id'] ?? null) === $order->id
                    && ($context['order_code'] ?? null) === $order->order_code
                    && ($context['status'] ?? null) === OrderStatus::Completed->value
                    && ($context['error'] ?? null) === 'Queue unavailable'
                    && ($context['exception_class'] ?? null) === \RuntimeException::class
                    && ! array_key_exists('email', $context);
            });
    }
}

<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderCompletedMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderNotificationEmail
{
    // 注文ステータス変更イベントを処理し、対応するメール通知を送信する
    public function handle(OrderStatusChanged $event): void
    {
        $mailable = match ($event->newStatus) {
            OrderStatus::Completed => OrderCompletedMail::class,
            OrderStatus::Cancelled => OrderCancelledMail::class,
            default => null,
        };

        if ($mailable === null) {
            return;
        }

        $order = $event->order;
        $order->load(['user', 'tenant', 'items.options']);

        $email = $order->user?->email;

        if ($email === null) {
            Log::warning('注文通知メールの送信をスキップしました: ユーザーのメールアドレスが未設定です', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'new_status' => $event->newStatus->value,
            ]);

            return;
        }

        try {
            Mail::to($email)->queue(new $mailable($order));
            Log::info('注文通知メールをキューに追加しました', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'status' => $event->newStatus->value,
            ]);
        } catch (\Throwable $e) {
            Log::error('注文通知メールのキュー追加に失敗しました', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'status' => $event->newStatus->value,
                'error' => $e->getMessage(),
                'exception_class' => $e::class,
            ]);
        }
    }
}

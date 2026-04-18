<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PaymentCompleted;
use Illuminate\Support\Facades\Log;

/**
 * 決済完了ログ記録リスナー
 */
class LogPaymentCompleted
{
    public function handle(PaymentCompleted $event): void
    {
        Log::info('決済が完了しました', [
            'payment_id' => $event->payment->id,
            'order_id' => $event->order->id,
            'order_code' => $event->order->order_code,
        ]);
    }
}

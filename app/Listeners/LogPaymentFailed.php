<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PaymentFailed;
use Illuminate\Support\Facades\Log;

/**
 * 決済失敗ログ記録リスナー
 */
class LogPaymentFailed
{
    public function handle(PaymentFailed $event): void
    {
        Log::warning('決済が失敗しました', [
            'payment_id' => $event->payment->id,
            'order_id' => $event->order->id,
            'reason' => $event->reason,
        ]);
    }
}

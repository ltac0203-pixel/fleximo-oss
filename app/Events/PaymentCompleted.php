<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 決済完了イベント
 */
class PaymentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Payment $payment,
        public readonly Order $order,
    ) {}
}

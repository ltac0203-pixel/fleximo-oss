<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 決済失敗イベント
 */
class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Payment $payment,
        public readonly Order $order,
        public readonly ?string $reason = null,
    ) {}
}

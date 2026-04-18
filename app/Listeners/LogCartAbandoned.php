<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CartAbandoned;
use Illuminate\Support\Facades\Log;

/**
 * カート放棄ログ記録リスナー
 */
class LogCartAbandoned
{
    public function handle(CartAbandoned $event): void
    {
        Log::info('カートが放棄されました', [
            'cart_id' => $event->cart->id,
            'user_id' => $event->cart->user_id,
            'tenant_id' => $event->cart->tenant_id,
            'item_count' => $event->cart->items()->count(),
        ]);
    }
}

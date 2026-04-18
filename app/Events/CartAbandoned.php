<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Cart;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * カート放棄イベント
 */
class CartAbandoned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Cart $cart,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Cart;
use App\Services\CartService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ClearExpiredCartsCommand extends Command
{
    protected $signature = 'carts:clear-expired';

    protected $description = '日付が変わった古いカートのアイテムを削除します';

    public function handle(CartService $cartService): int
    {
        $expiredCarts = Cart::where('updated_at', '<', Carbon::today())
            ->whereHas('items')
            ->get();

        $count = $expiredCarts->count();

        if ($count === 0) {
            $this->info('期限切れカートはありませんでした。');

            return self::SUCCESS;
        }

        foreach ($expiredCarts as $cart) {
            $cartService->clearCart($cart);
        }

        $this->info("期限切れカートを {$count} 件削除しました。");
        \Log::info("ClearExpiredCartsCommand: 期限切れカートを {$count} 件削除しました。");

        return self::SUCCESS;
    }
}

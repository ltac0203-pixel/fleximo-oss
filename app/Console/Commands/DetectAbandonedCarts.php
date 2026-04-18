<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\CartAbandoned;
use App\Models\Cart;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DetectAbandonedCarts extends Command
{
    protected $signature = 'carts:detect-abandoned';

    protected $description = '一定時間更新のないカートを検出し、放棄イベントを発行する';

    public function handle(): int
    {
        $thresholdMinutes = (int) config('cart.abandoned_threshold_minutes', 30);
        $threshold = Carbon::now()->subMinutes($thresholdMinutes);

        $carts = Cart::withoutGlobalScopes()
            ->where('updated_at', '<=', $threshold)
            ->where('updated_at', '>=', Carbon::today())
            ->whereHas('items')
            ->get();

        $count = $carts->count();

        if ($count === 0) {
            $this->info('放棄カートはありませんでした。');

            return self::SUCCESS;
        }

        foreach ($carts as $cart) {
            event(new CartAbandoned($cart));
        }

        $this->info("放棄カートを {$count} 件検出しました。");
        Log::info('DetectAbandonedCarts: 放棄カートを検出しました', ['count' => $count]);

        return self::SUCCESS;
    }
}

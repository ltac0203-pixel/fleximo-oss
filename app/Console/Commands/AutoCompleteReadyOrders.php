<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoCompleteReadyOrders extends Command
{
    protected $signature = 'orders:auto-complete-ready';

    protected $description = '準備完了から一定時間経過した注文を自動完了にする';

    public function handle(): int
    {
        $fallbackMinutes = (int) config('kds.ready_auto_complete_fallback_minutes', 10);
        $threshold = Carbon::now()->subMinutes($fallbackMinutes);

        $orders = Order::withoutGlobalScopes()
            ->where('status', OrderStatus::Ready->value)
            ->where('ready_at', '<=', $threshold)
            ->get();

        $count = $orders->count();

        if ($count === 0) {
            $this->info('自動完了対象の注文はありませんでした。');

            return self::SUCCESS;
        }

        foreach ($orders as $order) {
            $previousStatus = $order->status;
            $order->markAsCompleted();
            event(new OrderStatusChanged($order, $previousStatus, OrderStatus::Completed));
        }

        $this->info("準備完了から {$fallbackMinutes} 分以上経過した注文を {$count} 件自動完了しました。");
        \Log::info("AutoCompleteReadyOrders: 準備完了から {$fallbackMinutes} 分以上経過した注文を {$count} 件自動完了しました。");

        return self::SUCCESS;
    }
}

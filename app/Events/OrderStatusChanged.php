<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// 注文ステータス変更イベント
// KDS（キッチンディスプレイシステム）はポーリング方式で更新を取得するため、
// このイベントはリスナーや監査ログ用途で使用される。
class OrderStatusChanged
{
    use Dispatchable, SerializesModels;

    // 新しいイベントインスタンスを作成する。
    public function __construct(
        public readonly Order $order,
        public readonly OrderStatus $previousStatus,
        public readonly OrderStatus $newStatus,
    ) {}
}

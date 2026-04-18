<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class KdsService
{
    public function getKdsOrders(array $statuses = [], ?Carbon $businessDate = null, ?Carbon $updatedSince = null): Collection
    {
        $businessDate ??= Carbon::today();

        $query = Order::withKdsDetails()
            ->forBusinessDate($businessDate);

        if (empty($statuses)) {
            // 未指定時はキッチンで対応が必要な注文のみ表示する（completed/cancelledは除外）
            $query->kdsVisible();
        } else {
            $query->whereIn('status', $statuses);
        }

        if ($updatedSince) {
            $query->where('updated_at', '>=', $updatedSince);
        }

        return $query->orderByRaw('COALESCE(paid_at, accepted_at) ASC')->get();
    }

    // 注文詳細を取得する（items.options をイーガーロード）
    public function getOrderWithDetails(Order $order): Order
    {
        return $order->loadKdsDetails();
    }

    // 注文ステータスを更新する
    public function updateOrderStatus(Order $order, OrderStatus $newStatus): Order
    {
        $previousStatus = $order->status;

        if (! $previousStatus->canTransitionTo($newStatus)) {
            throw new InvalidStatusTransitionException($previousStatus, $newStatus);
        }

        // Orderモデルの各メソッドがタイムスタンプ更新も担うため、直接status変更ではなく専用メソッドを使う
        match ($newStatus) {
            OrderStatus::Accepted => $order->markAsAccepted(),
            OrderStatus::InProgress => $order->markAsInProgress(),
            OrderStatus::Ready => $order->markAsReady(),
            OrderStatus::Completed => $order->markAsCompleted(),
            OrderStatus::Cancelled => $order->markAsCancelled(),
            default => throw new InvalidStatusTransitionException(
                $previousStatus,
                $newStatus,
                'KDS does not support this status transition'
            ),
        };

        // リアルタイム通知や他サービスとの連携のため、ステータス変更をイベントで伝播する
        event(new OrderStatusChanged($order, $previousStatus, $newStatus));

        // 誰がいつステータスを変更したかを追跡可能にするため、監査ログを残す
        AuditLogger::log(
            action: AuditAction::KdsOrderStatusChanged,
            target: $order,
            changes: [
                'old' => ['status' => $previousStatus->value],
                'new' => ['status' => $newStatus->value],
                'metadata' => [
                    'order_code' => $order->order_code,
                ],
            ],
            tenantId: $order->tenant_id
        );

        return $order;
    }

    // 経過時間が閾値を超えた注文を取得する（警告用）
    public function getElapsedOrders(?int $thresholdMinutes = null): Collection
    {
        $thresholdMinutes ??= (int) config('kds.warning_threshold_minutes', 15);
        $threshold = Carbon::now()->subMinutes($thresholdMinutes);

        return Order::withKdsAlert()
            ->active()
            ->forBusinessDate(Carbon::today())
            ->where(function ($query) use ($threshold) {
                // 受付済みだが未着手の注文は対応漏れの可能性が高いため警告対象にする
                $query->where(function ($q) use ($threshold) {
                    $q->where('status', OrderStatus::Accepted)
                        ->where('accepted_at', '<=', $threshold);
                })
                    // 調理中のまま長時間経過した注文も、提供遅延として警告対象にする
                    ->orWhere(function ($q) use ($threshold) {
                        $q->where('status', OrderStatus::InProgress)
                            ->where('in_progress_at', '<=', $threshold);
                    });
            })
            ->orderBy('accepted_at', 'asc')
            ->get();
    }

    // 注文の経過時間（秒）を計算する
    public function getElapsedSeconds(Order $order): int
    {
        $startedAt = match ($order->status) {
            OrderStatus::Paid => $order->paid_at,
            OrderStatus::Accepted => $order->accepted_at,
            OrderStatus::InProgress => $order->in_progress_at,
            OrderStatus::Ready => $order->ready_at,
            default => $order->created_at,
        };

        if (! $startedAt) {
            return 0;
        }

        return (int) $startedAt->diffInSeconds(Carbon::now(), false);
    }
}

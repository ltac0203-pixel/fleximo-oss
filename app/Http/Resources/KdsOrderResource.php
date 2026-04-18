<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @mixin Order
class KdsOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $elapsedSeconds = $this->calculateElapsedSeconds();

        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'items' => KdsOrderItemResource::collection($this->whenLoaded('items')),
            'item_count' => $this->items_count ?? $this->items->count(),
            'elapsed_seconds' => $elapsedSeconds,
            'elapsed_display' => $this->formatElapsedTime($elapsedSeconds),
            'is_warning' => $this->isWarning($elapsedSeconds),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'in_progress_at' => $this->in_progress_at?->toIso8601String(),
            'ready_at' => $this->ready_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    // 経過時間を表示用にフォーマットする
    protected function formatElapsedTime(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}秒";
        }

        $minutes = (int) floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return "{$minutes}分{$remainingSeconds}秒";
        }

        $hours = (int) floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours}時間{$remainingMinutes}分";
    }

    // 警告が必要な経過時間かどうかを判定する
    protected function isWarning(int $seconds): bool
    {
        $thresholdMinutes = (int) config('kds.warning_threshold_minutes', 15);

        return $seconds >= $thresholdMinutes * 60;
    }

    // 経過秒数を計算する
    protected function calculateElapsedSeconds(): int
    {
        $startedAt = match ($this->status) {
            OrderStatus::Paid => $this->paid_at,
            OrderStatus::Accepted => $this->accepted_at,
            OrderStatus::InProgress => $this->in_progress_at,
            OrderStatus::Ready => $this->ready_at,
            default => $this->created_at,
        };

        if (! $startedAt) {
            return 0;
        }

        return (int) $startedAt->diffInSeconds(Carbon::now(), false);
    }
}

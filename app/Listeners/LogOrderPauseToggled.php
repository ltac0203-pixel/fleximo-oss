<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TenantOrderPaused;
use Illuminate\Support\Facades\Log;

/**
 * 注文受付停止/再開ログ記録リスナー
 */
class LogOrderPauseToggled
{
    public function handle(TenantOrderPaused $event): void
    {
        Log::info('テナントの注文受付状態が変更されました', [
            'tenant_id' => $event->tenant->id,
            'tenant_name' => $event->tenant->name,
            'is_paused' => $event->isPaused,
        ]);
    }
}

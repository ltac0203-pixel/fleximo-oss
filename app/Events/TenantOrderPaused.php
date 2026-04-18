<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * テナント注文受付停止/再開イベント
 */
class TenantOrderPaused
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly bool $isPaused,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * テナントメニュー変更イベント
 */
class TenantMenuUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $changeType, // 'created', 'updated', 'deleted', 'sold_out_toggled'
    ) {}
}

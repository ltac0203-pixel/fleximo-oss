<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TenantMenuUpdated;
use App\Support\MenuCacheKeys;

class InvalidateMenuCache
{
    public function handle(TenantMenuUpdated $event): void
    {
        MenuCacheKeys::invalidate($event->tenantId, [
            'change_type' => $event->changeType,
        ]);
    }
}

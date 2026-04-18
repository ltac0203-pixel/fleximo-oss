<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function handle(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $user = $event->user;

        AuditLogger::log(
            action: AuditAction::Logout,
            target: $user,
            changes: [
                'metadata' => [
                    'guard' => $event->guard,
                    'role' => $user->role?->value,
                ],
            ],
            tenantId: $user->getTenantId(),
        );
    }
}

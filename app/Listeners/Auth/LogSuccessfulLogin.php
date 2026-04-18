<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $user = $event->user;

        $user->forceFill([
            'last_login_at' => now(),
        ])->saveQuietly();

        AuditLogger::log(
            action: AuditAction::Login,
            target: $user,
            changes: [
                'metadata' => [
                    'guard' => $event->guard,
                    'role' => $user->role?->value,
                    'login_route_type' => $this->resolveLoginRouteType(),
                ],
            ],
            tenantId: $user->getTenantId(),
        );
    }

    private function resolveLoginRouteType(): string
    {
        $request = request();

        if ($request->is('for-business/login')) {
            return 'business';
        }

        if ($request->is('login')) {
            return 'customer';
        }

        return 'unknown';
    }
}

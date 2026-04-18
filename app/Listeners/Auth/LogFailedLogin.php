<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        AuditLogger::log(
            action: AuditAction::LoginFailed,
            target: $user,
            changes: [
                'metadata' => $this->buildMetadata($event, $user),
            ],
            tenantId: $user?->getTenantId(),
        );
    }

    /** @return array<string, string> */
    private function buildMetadata(Failed $event, ?User $user): array
    {
        return array_filter([
            'guard' => $event->guard,
            'email' => $event->credentials['email'] ?? null,
            'role' => $user?->role?->value,
            'failure_reason' => 'invalid_credentials',
            'login_route_type' => $this->resolveLoginRouteType(),
        ], static fn (mixed $value): bool => $value !== null);
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

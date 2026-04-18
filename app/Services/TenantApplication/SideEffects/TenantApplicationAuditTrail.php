<?php

declare(strict_types=1);

namespace App\Services\TenantApplication\SideEffects;

use App\Enums\AuditAction;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\AuditLogger;

class TenantApplicationAuditTrail
{
    public function logReviewStarted(TenantApplication $application, User $reviewer): void
    {
        AuditLogger::log(
            action: AuditAction::TenantApplicationReviewStarted,
            target: $application,
            changes: [
                'metadata' => [
                    'reviewer_id' => $reviewer->id,
                    'reviewer_name' => $reviewer->name,
                ],
            ]
        );
    }

    public function logApproved(
        TenantApplication $application,
        User $reviewer,
        Tenant $tenant,
        User $user,
    ): void {
        AuditLogger::log(
            action: AuditAction::TenantApplicationApproved,
            target: $application,
            changes: [
                'metadata' => [
                    'reviewer_id' => $reviewer->id,
                    'reviewer_name' => $reviewer->name,
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                ],
            ]
        );
    }

    public function logRejected(TenantApplication $application, User $reviewer, string $reason): void
    {
        AuditLogger::log(
            action: AuditAction::TenantApplicationRejected,
            target: $application,
            changes: [
                'metadata' => [
                    'reviewer_id' => $reviewer->id,
                    'reviewer_name' => $reviewer->name,
                    'rejection_reason' => $reason,
                ],
            ]
        );
    }
}

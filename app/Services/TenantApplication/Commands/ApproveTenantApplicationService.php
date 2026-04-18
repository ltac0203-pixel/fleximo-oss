<?php

declare(strict_types=1);

namespace App\Services\TenantApplication\Commands;

use App\Enums\TenantApplicationStatus;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\TenantApplication\SideEffects\TenantApplicationAuditTrail;
use App\Services\TenantApplication\SideEffects\TenantApplicationNotifier;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;

class ApproveTenantApplicationService
{
    public function __construct(
        private TenantApplicationNotifier $notifier,
        private TenantApplicationAuditTrail $auditTrail
    ) {}

    public function handle(TenantApplication $application, User $reviewer): TenantApplication
    {
        if (! $application->canBeApproved()) {
            throw new \InvalidArgumentException('この申し込みは承認できません');
        }

        return DB::transaction(function () use ($application, $reviewer) {
            return $this->approveNewFlow($application, $reviewer);
        });
    }

    private function approveNewFlow(TenantApplication $application, User $reviewer): TenantApplication
    {
        $tenant = Tenant::findOrFail($application->created_tenant_id);
        $user = User::findOrFail($application->applicant_user_id);

        $tenant->status = TenantStatus::Active;
        $tenant->is_active = true;
        $tenant->is_approved = true;
        $tenant->save();

        TenantService::invalidateActiveTenantListCache();

        $application->reviewed_by = $reviewer->id;
        $application->status = TenantApplicationStatus::Approved;
        $application->reviewed_at = now();
        $application->save();

        $this->notifier->notifyApproved(
            application: $application,
            tenant: $tenant,
            user: $user,
            token: null,
            flowLabel: '新フロー'
        );

        $this->auditTrail->logApproved(
            application: $application,
            reviewer: $reviewer,
            tenant: $tenant,
            user: $user
        );

        return $application->fresh(['reviewer', 'createdTenant']);
    }
}

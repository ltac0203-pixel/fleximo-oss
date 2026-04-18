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

class RejectTenantApplicationService
{
    public function __construct(
        private TenantApplicationNotifier $notifier,
        private TenantApplicationAuditTrail $auditTrail
    ) {}

    public function handle(TenantApplication $application, User $reviewer, string $reason): TenantApplication
    {
        if (! $application->canBeRejected()) {
            throw new \InvalidArgumentException('この申し込みは却下できません');
        }

        return DB::transaction(function () use ($application, $reviewer, $reason) {
            if ($application->applicant_user_id) {
                $user = User::find($application->applicant_user_id);
                if ($user) {
                    $user->is_active = false;
                    $user->save();
                    $user->tokens()->delete();
                }
            }

            if ($application->created_tenant_id) {
                $tenant = Tenant::find($application->created_tenant_id);
                if ($tenant) {
                    $tenant->status = TenantStatus::Rejected;
                    $tenant->is_active = false;
                    $tenant->save();
                    TenantService::invalidateActiveTenantListCache();
                }
            }

            $application->reviewed_by = $reviewer->id;
            $application->status = TenantApplicationStatus::Rejected;
            $application->rejection_reason = $reason;
            $application->reviewed_at = now();
            $application->save();

            $this->notifier->notifyRejected($application, $reason);
            $this->auditTrail->logRejected($application, $reviewer, $reason);

            return $application->fresh(['reviewer']);
        });
    }
}

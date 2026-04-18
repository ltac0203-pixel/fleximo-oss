<?php

declare(strict_types=1);

namespace App\Services\TenantApplication\Commands;

use App\Enums\TenantApplicationStatus;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\TenantApplication\SideEffects\TenantApplicationAuditTrail;
use Illuminate\Support\Facades\DB;

class StartReviewTenantApplicationService
{
    public function __construct(
        private TenantApplicationAuditTrail $auditTrail
    ) {}

    public function handle(TenantApplication $application, User $reviewer): TenantApplication
    {
        if (! $application->canStartReview()) {
            throw new \InvalidArgumentException('この申し込みは審査開始できません');
        }

        return DB::transaction(function () use ($application, $reviewer) {
            $application->reviewed_by = $reviewer->id;
            $application->status = TenantApplicationStatus::UnderReview;
            $application->reviewed_at = now();
            $application->save();

            $this->auditTrail->logReviewStarted($application, $reviewer);

            return $application->fresh(['reviewer']);
        });
    }
}

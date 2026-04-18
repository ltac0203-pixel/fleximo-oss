<?php

declare(strict_types=1);

namespace App\Services\TenantApplication;

use App\Models\TenantApplication;

class TenantApplicationLookupService
{
    // テナントIDまたはユーザーIDで申請を検索する
    public function findForTenantOrUser(int $tenantId, int $userId): ?TenantApplication
    {
        return TenantApplication::where('created_tenant_id', $tenantId)
            ->orWhere('applicant_user_id', $userId)
            ->first();
    }
}

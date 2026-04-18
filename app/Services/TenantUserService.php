<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Exceptions\UserAlreadyAssignedToTenantException;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TenantUserService
{
    // ユーザーをテナントに割り当てる
    public function assignUserToTenant(
        User $user,
        Tenant $tenant,
        TenantUserRole $role
    ): TenantUser {
        if ($user->tenantUser()->exists()) {
            throw new UserAlreadyAssignedToTenantException($user);
        }

        return DB::transaction(function () use ($user, $tenant, $role) {
            $userRole = $role === TenantUserRole::Admin
                ? UserRole::TenantAdmin
                : UserRole::TenantStaff;

            $user->role = $userRole;
            $user->save();

            $tenantUser = new TenantUser([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
            ]);
            $tenantUser->role = $role;
            $tenantUser->save();

            AuditLogger::log(
                action: AuditAction::TenantUserAssigned,
                target: $tenantUser,
                changes: [
                    'metadata' => [
                        'user_id' => $user->id,
                        'tenant_id' => $tenant->id,
                        'role' => $role->value,
                    ],
                ],
                tenantId: $tenant->id
            );

            return $tenantUser;
        });
    }

    // ユーザーをテナントから削除する
    public function removeUserFromTenant(User $user): void
    {
        $tenantUser = $user->tenantUser()->firstOrFail();
        $tenantId = $tenantUser->tenant_id;

        DB::transaction(function () use ($user, $tenantUser, $tenantId) {
            $user->role = UserRole::Customer;
            $user->save();
            $tenantUser->delete();

            AuditLogger::log(
                action: AuditAction::TenantUserRemoved,
                target: null,
                changes: [
                    'metadata' => [
                        'user_id' => $user->id,
                        'tenant_id' => $tenantId,
                    ],
                ],
                tenantId: $tenantId
            );
        });
    }

    // ユーザーのテナントロールを変更する
    public function updateUserRole(
        TenantUser $tenantUser,
        TenantUserRole $newRole
    ): TenantUser {
        $oldRole = $tenantUser->role;

        if ($oldRole === $newRole) {
            return $tenantUser;
        }

        return DB::transaction(function () use ($tenantUser, $newRole, $oldRole) {
            $tenantUser->role = $newRole;
            $tenantUser->save();

            $userRole = $newRole === TenantUserRole::Admin
                ? UserRole::TenantAdmin
                : UserRole::TenantStaff;

            $tenantUser->user->role = $userRole;
            $tenantUser->user->save();

            AuditLogger::log(
                action: AuditAction::TenantUserRoleChanged,
                target: $tenantUser,
                changes: [
                    'old' => ['role' => $oldRole->value],
                    'new' => ['role' => $newRole->value],
                ],
                tenantId: $tenantUser->tenant_id
            );

            return $tenantUser;
        });
    }
}

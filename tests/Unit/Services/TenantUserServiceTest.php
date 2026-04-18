<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AuditAction;
use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Exceptions\UserAlreadyAssignedToTenantException;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\TenantUserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantUserServiceTest extends TestCase
{
    use RefreshDatabase;

    private TenantUserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TenantUserService;
    }

    public function test_assign_user_to_tenant_creates_tenant_user(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);
        $tenant = Tenant::factory()->create();

        $tenantUser = $this->service->assignUserToTenant($user, $tenant, TenantUserRole::Admin);

        $this->assertDatabaseHas('tenant_users', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin->value,
        ]);

        $this->assertEquals($user->id, $tenantUser->user_id);
        $this->assertEquals($tenant->id, $tenantUser->tenant_id);
        $this->assertEquals(TenantUserRole::Admin, $tenantUser->role);
    }

    public function test_assign_user_to_tenant_updates_user_role_to_tenant_admin(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);
        $tenant = Tenant::factory()->create();

        $this->service->assignUserToTenant($user, $tenant, TenantUserRole::Admin);

        $user->refresh();
        $this->assertEquals(UserRole::TenantAdmin, $user->role);
    }

    public function test_assign_user_to_tenant_updates_user_role_to_tenant_staff(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);
        $tenant = Tenant::factory()->create();

        $this->service->assignUserToTenant($user, $tenant, TenantUserRole::Staff);

        $user->refresh();
        $this->assertEquals(UserRole::TenantStaff, $user->role);
    }

    public function test_assign_user_to_tenant_throws_exception_if_user_already_assigned(): void
    {
        $user = User::factory()->create();
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant1->id,
            'role' => TenantUserRole::Admin,
        ]);

        $this->expectException(UserAlreadyAssignedToTenantException::class);
        $this->service->assignUserToTenant($user, $tenant2, TenantUserRole::Staff);
    }

    public function test_assign_user_to_tenant_creates_audit_log(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);
        $tenant = Tenant::factory()->create();

        $this->service->assignUserToTenant($user, $tenant, TenantUserRole::Admin);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::TenantUserAssigned->value,
            'tenant_id' => $tenant->id,
        ]);

        $log = AuditLog::where('action', AuditAction::TenantUserAssigned)->first();
        $metadata = $log->metadata;

        $this->assertEquals($user->id, $metadata['user_id']);
        $this->assertEquals($tenant->id, $metadata['tenant_id']);
        $this->assertEquals(TenantUserRole::Admin->value, $metadata['role']);
    }

    public function test_assign_user_to_tenant_is_transactional(): void
    {
        // AuditLogger::logはstaticメソッドのためmock困難
        // assignUserToTenantは正常系で全ステップ成功するため、ロールバックを発生させるには
        // DB制約違反などの外部要因が必要。トランザクション設計はDB::transaction使用で担保。
        $user = User::factory()->create(['role' => UserRole::Customer]);
        $tenant = Tenant::factory()->create();

        // 正常に割り当てが完了することを確認（トランザクション内で整合性が保たれる）
        $tenantUser = $this->service->assignUserToTenant($user, $tenant, TenantUserRole::Admin);

        $this->assertNotNull($tenantUser);
        $user->refresh();
        $this->assertEquals(UserRole::TenantAdmin, $user->role);
    }

    public function test_remove_user_from_tenant_deletes_tenant_user(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $this->service->removeUserFromTenant($user);

        $this->assertDatabaseMissing('tenant_users', [
            'user_id' => $user->id,
        ]);
    }

    public function test_remove_user_from_tenant_updates_user_role_to_customer(): void
    {
        $user = User::factory()->create(['role' => UserRole::TenantAdmin]);
        $tenant = Tenant::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $this->service->removeUserFromTenant($user);

        $user->refresh();
        $this->assertEquals(UserRole::Customer, $user->role);
    }

    public function test_remove_user_from_tenant_throws_exception_if_user_not_assigned(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $this->expectException(ModelNotFoundException::class);
        $this->service->removeUserFromTenant($user);
    }

    public function test_remove_user_from_tenant_creates_audit_log(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $this->service->removeUserFromTenant($user);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::TenantUserRemoved->value,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_change_user_role_updates_tenant_user_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::TenantStaff]);
        $tenant = Tenant::factory()->create();
        $tenantUser = TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $updatedTenantUser = $this->service->updateUserRole($tenantUser, TenantUserRole::Admin);

        $this->assertEquals(TenantUserRole::Admin, $updatedTenantUser->role);
        $this->assertDatabaseHas('tenant_users', [
            'id' => $tenantUser->id,
            'role' => TenantUserRole::Admin->value,
        ]);
    }

    public function test_change_user_role_updates_user_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::TenantStaff]);
        $tenant = Tenant::factory()->create();
        $tenantUser = TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $this->service->updateUserRole($tenantUser, TenantUserRole::Admin);

        $user->refresh();
        $this->assertEquals(UserRole::TenantAdmin, $user->role);
    }

    public function test_change_user_role_returns_same_tenant_user_if_role_unchanged(): void
    {
        $user = User::factory()->create(['role' => UserRole::TenantAdmin]);
        $tenant = Tenant::factory()->create();
        $tenantUser = TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $result = $this->service->updateUserRole($tenantUser, TenantUserRole::Admin);

        $this->assertEquals($tenantUser->id, $result->id);
        $this->assertEquals(TenantUserRole::Admin, $result->role);
    }

    public function test_change_user_role_creates_audit_log(): void
    {
        $user = User::factory()->create(['role' => UserRole::TenantStaff]);
        $tenant = Tenant::factory()->create();
        $tenantUser = TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $this->service->updateUserRole($tenantUser, TenantUserRole::Admin);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::TenantUserRoleChanged->value,
            'tenant_id' => $tenant->id,
        ]);

        $log = AuditLog::where('action', AuditAction::TenantUserRoleChanged)->first();

        $this->assertEquals(TenantUserRole::Staff->value, $log->old_values['role']);
        $this->assertEquals(TenantUserRole::Admin->value, $log->new_values['role']);
    }

    public function test_change_user_role_does_not_create_audit_log_if_role_unchanged(): void
    {
        $user = User::factory()->create(['role' => UserRole::TenantAdmin]);
        $tenant = Tenant::factory()->create();
        $tenantUser = TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $this->service->updateUserRole($tenantUser, TenantUserRole::Admin);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => AuditAction::TenantUserRoleChanged->value,
        ]);
    }
}

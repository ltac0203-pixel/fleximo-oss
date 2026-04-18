<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Staff\CreateStaffData;
use App\DTOs\Staff\UpdateStaffData;
use App\Enums\AuditAction;
use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\StaffService;
use App\Services\TenantUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffServiceTest extends TestCase
{
    use RefreshDatabase;

    private StaffService $staffService;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffService = app(StaffService::class);
        $this->tenant = Tenant::factory()->create();
    }

    public function test_create_staff_creates_user_with_staff_role(): void
    {
        $data = new CreateStaffData(
            name: 'テストスタッフ',
            email: 'test@example.com',
            password: 'password123',
            phone: '090-1234-5678',
        );

        $staff = $this->staffService->createStaff($this->tenant, $data);

        $this->assertInstanceOf(User::class, $staff);
        $this->assertEquals('テストスタッフ', $staff->name);
        $this->assertEquals('test@example.com', $staff->email);
        $this->assertEquals('090-1234-5678', $staff->phone);
        $this->assertEquals(UserRole::TenantStaff, $staff->role);
        $this->assertTrue($staff->is_active);
        $this->assertTrue(Hash::check('password123', $staff->password));
    }

    public function test_create_staff_assigns_to_tenant(): void
    {
        $data = new CreateStaffData(
            name: 'テストスタッフ',
            email: 'test@example.com',
            password: 'password123',
        );

        $staff = $this->staffService->createStaff($this->tenant, $data);

        $this->assertDatabaseHas('tenant_users', [
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff->value,
        ]);

        $this->assertNotNull($staff->tenantUser);
        $this->assertEquals($this->tenant->id, $staff->tenantUser->tenant_id);
    }

    public function test_create_staff_creates_audit_log(): void
    {
        $data = new CreateStaffData(
            name: 'テストスタッフ',
            email: 'test@example.com',
            password: 'password123',
        );

        $staff = $this->staffService->createStaff($this->tenant, $data);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::StaffCreated->value,
            'auditable_type' => User::class,
            'auditable_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_create_staff_is_transactional(): void
    {
        $this->mock(TenantUserService::class, function ($mock) {
            $mock->shouldReceive('assignUserToTenant')
                ->andThrow(new \Exception('Test exception'));
        });

        // mockを適用した後にStaffServiceを再取得
        $staffService = app(StaffService::class);

        $data = new CreateStaffData(
            name: 'テストスタッフ',
            email: 'test@example.com',
            password: 'password123',
        );

        try {
            $staffService->createStaff($this->tenant, $data);
            $this->fail('Exception should have been thrown');
        } catch (\Exception $e) {

            $this->assertDatabaseMissing('users', [
                'email' => 'test@example.com',
            ]);
        }
    }

    public function test_update_staff_updates_only_provided_fields(): void
    {
        $staff = User::factory()->create([
            'role' => UserRole::TenantStaff,
            'name' => '元の名前',
            'email' => 'original@example.com',
            'phone' => '090-1111-1111',
        ]);
        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $updateData = new UpdateStaffData(
            name: '新しい名前',
            presentFields: ['name'],
        );

        $updatedStaff = $this->staffService->updateStaff($staff, $updateData);

        $this->assertEquals('新しい名前', $updatedStaff->name);
        $this->assertEquals('original@example.com', $updatedStaff->email);
        $this->assertEquals('090-1111-1111', $updatedStaff->phone);
    }

    public function test_update_staff_hashes_password(): void
    {
        $staff = User::factory()->create([
            'role' => UserRole::TenantStaff,
            'password' => 'oldpassword',  // Userモデルのcastsで自動ハッシュ化される
        ]);
        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $updateData = new UpdateStaffData(
            password: 'newpassword123',
            presentFields: ['password'],
        );

        $updatedStaff = $this->staffService->updateStaff($staff, $updateData);

        $this->assertTrue(Hash::check('newpassword123', $updatedStaff->password));

        $this->assertFalse(Hash::check('oldpassword', $updatedStaff->password));
    }

    public function test_update_staff_creates_audit_log(): void
    {
        $staff = User::factory()->create([
            'role' => UserRole::TenantStaff,
            'name' => '元の名前',
        ]);
        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $updateData = new UpdateStaffData(
            name: '新しい名前',
            presentFields: ['name'],
        );

        $this->staffService->updateStaff($staff, $updateData);

        $log = AuditLog::where('action', AuditAction::StaffUpdated->value)
            ->where('auditable_id', $staff->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(User::class, $log->auditable_type);
        $this->assertEquals($this->tenant->id, $log->tenant_id);

        $this->assertIsArray($log->old_values);
        $this->assertIsArray($log->new_values);
    }

    public function test_delete_staff_deactivates_user(): void
    {
        $staff = User::factory()->create([
            'role' => UserRole::TenantStaff,
            'is_active' => true,
        ]);
        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $this->staffService->deleteStaff($staff);

        $staff->refresh();
        $this->assertFalse($staff->is_active);
    }

    public function test_delete_staff_removes_tenant_relationship(): void
    {
        $staff = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $this->staffService->deleteStaff($staff);

        $this->assertDatabaseMissing('tenant_users', [
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_delete_staff_revokes_tokens(): void
    {
        $staff = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $token = $staff->createToken('test-token');

        $this->staffService->deleteStaff($staff);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $staff->id,
        ]);
    }

    public function test_delete_staff_creates_audit_log(): void
    {
        $staff = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $this->staffService->deleteStaff($staff);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::StaffDeleted->value,
            'auditable_type' => User::class,
            'auditable_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }
}

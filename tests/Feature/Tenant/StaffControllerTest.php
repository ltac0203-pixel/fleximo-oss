<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\AuditAction;
use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->admin = User::factory()->create([
            'role' => UserRole::TenantAdmin,
        ]);

        TenantUser::factory()->create([
            'user_id' => $this->admin->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Admin,
        ]);
        $this->admin->refresh();
    }

    public function test_tenant_admin_can_list_staff(): void
    {

        $staff1 = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $staff1->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $staff2 = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $staff2->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/staff');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'phone', 'is_active', 'created_at'],
                ],
            ]);
    }

    public function test_tenant_staff_can_list_staff(): void
    {
        $staff = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $response = $this->actingAs($staff)
            ->getJson('/api/tenant/staff');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'phone', 'is_active', 'created_at'],
                ],
            ]);
    }

    public function test_tenant_staff_cannot_create_staff(): void
    {
        $staff = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $response = $this->actingAs($staff)
            ->postJson('/api/tenant/staff', [
                'name' => 'New Staff',
                'email' => 'new@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertStatus(403);
    }

    public function test_tenant_admin_can_create_staff(): void
    {
        $staffData = [
            'name' => '鈴木花子',
            'email' => 'suzuki@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '090-1234-5678',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/staff', $staffData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'phone', 'is_active', 'created_at'],
            ])
            ->assertJson([
                'data' => [
                    'name' => '鈴木花子',
                    'email' => 'suzuki@example.com',
                    'phone' => '090-1234-5678',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'suzuki@example.com',
            'role' => UserRole::TenantStaff->value,
            'is_active' => true,
        ]);

        $user = User::where('email', 'suzuki@example.com')->first();
        $this->assertDatabaseHas('tenant_users', [
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::StaffCreated->value,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_staff_creation_fails_with_duplicate_email(): void
    {

        User::factory()->create(['email' => 'existing@example.com']);

        $staffData = [
            'name' => '佐藤太郎',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/staff', $staffData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_tenant_admin_can_view_staff_details(): void
    {
        $staff = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/staff/{$staff->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'phone', 'is_active', 'created_at'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $staff->id,
                    'email' => $staff->email,
                ],
            ]);
    }

    public function test_tenant_admin_cannot_view_other_tenant_staff(): void
    {

        $otherTenant = Tenant::factory()->create();
        $otherStaff = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $otherStaff->id,
            'tenant_id' => $otherTenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/staff/{$otherStaff->id}");

        // ensureStaffBelongsToTenant()でテナント所属チェックにより404
        $response->assertStatus(404);
    }

    public function test_tenant_admin_can_update_staff(): void
    {
        $staff = User::factory()->create([
            'role' => UserRole::TenantStaff,
            'name' => '田中太郎',
        ]);
        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $updateData = [
            'name' => '田中花子',
            'phone' => '080-9999-8888',
        ];

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/tenant/staff/{$staff->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => '田中花子',
                    'phone' => '080-9999-8888',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => '田中花子',
            'phone' => '080-9999-8888',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::StaffUpdated->value,
            'auditable_type' => User::class,
            'auditable_id' => $staff->id,
        ]);
    }

    public function test_tenant_admin_can_update_staff_password(): void
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

        $updateData = [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/tenant/staff/{$staff->id}", $updateData);

        $response->assertStatus(200);

        $staff->refresh();
        $this->assertTrue(Hash::check('newpassword123', $staff->password));
    }

    public function test_tenant_admin_can_deactivate_staff(): void
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

        $updateData = [
            'is_active' => false,
        ];

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/tenant/staff/{$staff->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'is_active' => false,
        ]);
    }

    public function test_tenant_admin_can_delete_staff(): void
    {
        $staff = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/tenant/staff/{$staff->id}");

        $response->assertStatus(204);
    }

    public function test_deleted_staff_is_deactivated(): void
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

        $this->actingAs($this->admin)
            ->deleteJson("/api/tenant/staff/{$staff->id}");

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::StaffDeleted->value,
            'auditable_type' => User::class,
            'auditable_id' => $staff->id,
        ]);
    }

    public function test_deleted_staff_tokens_are_revoked(): void
    {
        $staff = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $token = $staff->createToken('test-token');

        $this->actingAs($this->admin)
            ->deleteJson("/api/tenant/staff/{$staff->id}");

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $staff->id,
        ]);
    }

    public function test_tenant_staff_cannot_update_staff(): void
    {
        $staffUser = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $staffUser->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $targetStaff = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $targetStaff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $response = $this->actingAs($staffUser)
            ->patchJson("/api/tenant/staff/{$targetStaff->id}", [
                'name' => '変更名前',
            ]);

        $response->assertStatus(403);
    }

    public function test_tenant_staff_cannot_delete_staff(): void
    {
        $staffUser = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $staffUser->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $targetStaff = User::factory()->create(['role' => UserRole::TenantStaff]);
        TenantUser::factory()->create([
            'user_id' => $targetStaff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $response = $this->actingAs($staffUser)
            ->deleteJson("/api/tenant/staff/{$targetStaff->id}");

        $response->assertStatus(403);
    }

    public function test_customer_cannot_access_staff_endpoints(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($customer)
            ->getJson('/api/tenant/staff');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_staff_endpoints(): void
    {
        $response = $this->getJson('/api/tenant/staff');

        $response->assertStatus(401);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BusinessLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // テナントを作成
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => 'active',
        ]);
    }

    public function test_business_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/for-business/login');

        $response->assertStatus(200);
    }

    public function test_tenant_admin_can_authenticate_using_business_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::TenantAdmin,
            'email' => 'admin@test.com',
        ]);

        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $response = $this->post('/for-business/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/tenant/dashboard');
    }

    public function test_tenant_staff_can_authenticate_using_business_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::TenantStaff,
            'email' => 'staff@test.com',
        ]);

        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $response = $this->post('/for-business/login', [
            'email' => 'staff@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/tenant/kds');
    }

    public function test_admin_can_authenticate_using_business_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'system-admin@test.com',
        ]);

        $response = $this->post('/for-business/login', [
            'email' => 'system-admin@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/admin/dashboard');
    }

    public function test_customer_cannot_authenticate_using_business_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Customer,
            'email' => 'customer@test.com',
        ]);

        $response = $this->post('/for-business/login', [
            'email' => 'customer@test.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        // ロール情報漏洩防止: ロール不一致時も汎用エラーメッセージを返す
        $response->assertSessionMissing('customerLoginUrl');
    }

    public function test_tenant_admin_without_tenant_assignment_cannot_login(): void
    {
        User::factory()->create([
            'role' => UserRole::TenantAdmin,
            'email' => 'no-tenant-admin@test.com',
        ]);

        $response = $this->post('/for-business/login', [
            'email' => 'no-tenant-admin@test.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_tenant_staff_without_tenant_assignment_cannot_login(): void
    {
        User::factory()->create([
            'role' => UserRole::TenantStaff,
            'email' => 'no-tenant-staff@test.com',
        ]);

        $response = $this->post('/for-business/login', [
            'email' => 'no-tenant-staff@test.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_business_login_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::TenantAdmin,
            'email' => 'admin@test.com',
        ]);

        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $this->post('/for-business/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_business_login_deletes_existing_sessions(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::TenantAdmin,
            'email' => 'admin@test.com',
        ]);

        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        // 既存セッション（他デバイス等）を挿入
        $existingSessionId = 'fake-session-'.Str::random(10);
        DB::table('sessions')->insert([
            'id' => $existingSessionId,
            'user_id' => $user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'TestBrowser/1.0',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        $this->post('/for-business/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        // 既存セッションが即時削除されること
        $this->assertDatabaseMissing('sessions', ['id' => $existingSessionId]);
    }
}

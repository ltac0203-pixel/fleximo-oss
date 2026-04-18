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

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        // デフォルトは customer ロールなので customer.home にリダイレクトされる
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('customer.home', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Customer,
        ]);
        $token = 'logout-test-token';

        $response = $this->actingAs($user)
            ->withHeaders(['User-Agent' => 'Authentication Logout Test'])
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.21'])
            ->withSession(['_token' => $token])
            ->post('/logout', ['_token' => $token]);

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_tenant_admin_cannot_authenticate_using_customer_login(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::TenantAdmin,
            'email' => 'admin@test.com',
        ]);

        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        // ロール情報漏洩防止: ロール不一致時も汎用エラーメッセージを返す
        $response->assertSessionMissing('businessLoginUrl');
    }

    public function test_tenant_staff_cannot_authenticate_using_customer_login(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::TenantStaff,
            'email' => 'staff@test.com',
        ]);

        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $response = $this->post('/login', [
            'email' => 'staff@test.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $response->assertSessionMissing('businessLoginUrl');
    }

    public function test_admin_cannot_authenticate_using_customer_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'system-admin@test.com',
        ]);

        $response = $this->post('/login', [
            'email' => 'system-admin@test.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $response->assertSessionMissing('businessLoginUrl');
    }

    public function test_customer_login_preserves_existing_sessions(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        // 既存セッション（別デバイス等）を挿入
        $existingSessionId = 'fake-session-'.Str::random(10);
        DB::table('sessions')->insert([
            'id' => $existingSessionId,
            'user_id' => $user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'TestBrowser/1.0',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        // 顧客ログインでは既存セッションが維持されること（複数デバイス許容）
        $this->assertDatabaseHas('sessions', ['id' => $existingSessionId]);
    }
}

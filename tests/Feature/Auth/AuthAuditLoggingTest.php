<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\AuditAction;
use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_login_updates_last_login_at_and_creates_audit_log(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Customer,
            'last_login_at' => null,
        ]);

        $response = $this->withHeaders([
            'User-Agent' => 'Auth Audit Test',
        ])->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.10',
        ])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('customer.home', absolute: false));
        $this->assertAuthenticatedAs($user);

        $user->refresh();

        $this->assertNotNull($user->last_login_at);

        $log = $this->findLatestAuditLog(AuditAction::Login, $user);

        $this->assertSame($user->id, $log->user_id);
        $this->assertNull($log->tenant_id);
        $this->assertSame('203.0.113.10', $log->ip_address);
        $this->assertSame('Auth Audit Test', $log->user_agent);
        $this->assertSame([
            'guard' => 'web',
            'role' => UserRole::Customer->value,
            'login_route_type' => 'customer',
        ], $log->metadata);
    }

    public function test_customer_logout_creates_audit_log(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Customer,
        ]);
        $token = 'logout-audit-test-token';

        $response = $this->actingAs($user)->withHeaders([
            'User-Agent' => 'Logout Audit Test',
        ])->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.11',
        ])->withSession([
            '_token' => $token,
        ])->post('/logout', [
            '_token' => $token,
        ]);

        $response->assertRedirect(route('home'));
        $this->assertGuest();

        $log = $this->findLatestAuditLog(AuditAction::Logout, $user);

        $this->assertSame($user->id, $log->user_id);
        $this->assertNull($log->tenant_id);
        $this->assertSame('203.0.113.11', $log->ip_address);
        $this->assertSame('Logout Audit Test', $log->user_agent);
        $this->assertSame([
            'guard' => 'web',
            'role' => UserRole::Customer->value,
        ], $log->metadata);
    }

    public function test_invalid_password_login_creates_failed_login_audit_log_for_existing_user(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        $response = $this->from('/login')->withHeaders([
            'User-Agent' => 'Failed Login Audit Test',
        ])->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.12',
        ])->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $log = $this->findLatestAuditLog(AuditAction::LoginFailed, $user);

        $this->assertNull($log->user_id);
        $this->assertNull($log->tenant_id);
        $this->assertSame('203.0.113.12', $log->ip_address);
        $this->assertSame('Failed Login Audit Test', $log->user_agent);
        $this->assertSame([
            'guard' => 'web',
            'email' => $user->email,
            'role' => UserRole::Customer->value,
            'failure_reason' => 'invalid_credentials',
            'login_route_type' => 'customer',
        ], $log->metadata);
    }

    public function test_business_login_creates_audit_log_with_tenant_context(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::TenantAdmin,
            'last_login_at' => null,
        ]);

        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $response = $this->withHeaders([
            'User-Agent' => 'Business Login Audit Test',
        ])->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.13',
        ])->post('/for-business/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/tenant/dashboard');
        $this->assertAuthenticatedAs($user);

        $user->refresh();

        $this->assertNotNull($user->last_login_at);

        $log = $this->findLatestAuditLog(AuditAction::Login, $user);

        $this->assertSame($user->id, $log->user_id);
        $this->assertSame($tenant->id, $log->tenant_id);
        $this->assertSame('203.0.113.13', $log->ip_address);
        $this->assertSame('Business Login Audit Test', $log->user_agent);
        $this->assertSame([
            'guard' => 'web',
            'role' => UserRole::TenantAdmin->value,
            'login_route_type' => 'business',
        ], $log->metadata);
    }

    public function test_unknown_email_login_creates_failed_audit_log_without_target_user(): void
    {
        $response = $this->from('/login')->withHeaders([
            'User-Agent' => 'Unknown User Login Audit Test',
        ])->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.14',
        ])->post('/login', [
            'email' => 'unknown@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $log = AuditLog::query()
            ->where('action', AuditAction::LoginFailed->value)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
        $this->assertNull($log->tenant_id);
        $this->assertNull($log->auditable_type);
        $this->assertNull($log->auditable_id);
        $this->assertSame('203.0.113.14', $log->ip_address);
        $this->assertSame('Unknown User Login Audit Test', $log->user_agent);
        $this->assertSame([
            'guard' => 'web',
            'email' => 'unknown@example.com',
            'failure_reason' => 'invalid_credentials',
            'login_route_type' => 'customer',
        ], $log->metadata);
    }

    private function findLatestAuditLog(AuditAction $action, User $user): AuditLog
    {
        $log = AuditLog::query()
            ->where('action', $action->value)
            ->where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);

        return $log;
    }
}

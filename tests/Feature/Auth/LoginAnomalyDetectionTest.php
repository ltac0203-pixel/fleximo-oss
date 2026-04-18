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
use App\Notifications\SuspiciousLoginNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginAnomalyDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ip_change_creates_audit_log_and_sends_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        // 直前のログイン記録を作成
        AuditLog::create([
            'user_id' => $user->id,
            'action' => AuditAction::Login->value,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'metadata' => ['guard' => 'web', 'role' => 'customer'],
            'created_at' => now()->subMinutes(120),
        ]);

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ])->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.20',
        ])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::SuspiciousLoginIpChange->value,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);

        Notification::assertSentTo($user, SuspiciousLoginNotification::class);
    }

    public function test_frequency_creates_audit_log(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        $ip = '203.0.113.10';
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

        // 過去のログイン記録を作成（同一IP・同一デバイスで閾値-1回）
        for ($i = 0; $i < 2; $i++) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => AuditAction::Login->value,
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'metadata' => ['guard' => 'web', 'role' => 'customer'],
                'created_at' => now()->subMinutes(10 * ($i + 1)),
            ]);
        }

        $this->withHeaders([
            'User-Agent' => $ua,
        ])->withServerVariables([
            'REMOTE_ADDR' => $ip,
        ])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::SuspiciousLoginFrequency->value,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    public function test_new_device_creates_audit_log_and_sends_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        // 過去のログイン記録（Chrome/Windows）
        AuditLog::create([
            'user_id' => $user->id,
            'action' => AuditAction::Login->value,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'metadata' => ['guard' => 'web', 'role' => 'customer'],
            'created_at' => now()->subHours(24),
        ]);

        // 新しいデバイス（Firefox/Linux）でログイン
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0',
        ])->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.10',
        ])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::SuspiciousLoginNewDevice->value,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);

        Notification::assertSentTo($user, SuspiciousLoginNotification::class);
    }

    public function test_normal_login_does_not_create_anomaly_logs(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        $ip = '203.0.113.10';
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

        // 直前のログイン（同一IP・同一デバイス、2時間前なので高頻度にならない）
        AuditLog::create([
            'user_id' => $user->id,
            'action' => AuditAction::Login->value,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'metadata' => ['guard' => 'web', 'role' => 'customer'],
            'created_at' => now()->subHours(2),
        ]);

        $this->withHeaders([
            'User-Agent' => $ua,
        ])->withServerVariables([
            'REMOTE_ADDR' => $ip,
        ])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => AuditAction::SuspiciousLoginIpChange->value,
            'auditable_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => AuditAction::SuspiciousLoginFrequency->value,
            'auditable_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => AuditAction::SuspiciousLoginNewDevice->value,
            'auditable_id' => $user->id,
        ]);

        Notification::assertNotSentTo($user, SuspiciousLoginNotification::class);
    }

    public function test_business_login_also_triggers_anomaly_detection(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $user = User::factory()->create([
            'role' => UserRole::TenantAdmin,
        ]);
        TenantUser::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        // 過去のログイン（別IP）
        AuditLog::create([
            'user_id' => $user->id,
            'action' => AuditAction::Login->value,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'metadata' => ['guard' => 'web', 'role' => 'tenant_admin'],
            'created_at' => now()->subHours(2),
        ]);

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ])->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.30',
        ])->post('/for-business/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::SuspiciousLoginIpChange->value,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    public function test_notification_not_sent_during_cooldown(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        // クールダウンキャッシュをセット
        Cache::put("login_anomaly_notification:{$user->id}:auth.suspicious_login.ip_change", true, now()->addMinutes(60));
        Cache::put("login_anomaly_notification:{$user->id}:auth.suspicious_login.new_device", true, now()->addMinutes(60));

        // 過去のログイン
        AuditLog::create([
            'user_id' => $user->id,
            'action' => AuditAction::Login->value,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'metadata' => ['guard' => 'web', 'role' => 'customer'],
            'created_at' => now()->subHours(2),
        ]);

        // 異なるIP＋異なるデバイスでログイン
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0',
        ])->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.20',
        ])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        // 監査ログは記録される
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::SuspiciousLoginIpChange->value,
            'auditable_id' => $user->id,
        ]);

        // 通知はクールダウン中なので送信されない
        Notification::assertNotSentTo($user, SuspiciousLoginNotification::class);
    }

    public function test_detection_error_does_not_break_login(): void
    {
        // LoginAnomalyDetectorをモックして例外を投げさせる
        $this->mock(\App\Services\LoginAnomalyDetector::class, function ($mock) {
            $mock->shouldReceive('detect')
                ->andThrow(new \RuntimeException('Detection failed'));
        });

        $user = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        $response = $this->withHeaders([
            'User-Agent' => 'Test Agent',
        ])->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.10',
        ])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // ログインは成功する
        $this->assertAuthenticatedAs($user);
    }

    public function test_detection_disabled_creates_no_anomaly_logs(): void
    {
        Notification::fake();
        Config::set('login_anomaly.enabled', false);

        $user = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        // 過去のログイン（別IP、別デバイス）
        AuditLog::create([
            'user_id' => $user->id,
            'action' => AuditAction::Login->value,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'metadata' => ['guard' => 'web', 'role' => 'customer'],
            'created_at' => now()->subHours(2),
        ]);

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0',
        ])->withServerVariables([
            'REMOTE_ADDR' => '198.51.100.20',
        ])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => AuditAction::SuspiciousLoginIpChange->value,
            'auditable_id' => $user->id,
        ]);

        Notification::assertNotSentTo($user, SuspiciousLoginNotification::class);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\LoginAnomalyDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LoginAnomalyDetectorTest extends TestCase
{
    use RefreshDatabase;

    private LoginAnomalyDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new LoginAnomalyDetector;
    }

    private function createLoginLog(User $user, array $overrides = []): AuditLog
    {
        return AuditLog::create(array_merge([
            'user_id' => $user->id,
            'action' => AuditAction::Login->value,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => '203.0.113.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'created_at' => now(),
        ], $overrides));
    }

    private function createRequest(string $ip = '203.0.113.1', string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'): Request
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ]);

        return $request;
    }

    // ========================================
    // 全体制御
    // ========================================

    public function test_returns_empty_when_detection_disabled(): void
    {
        Config::set('login_anomaly.enabled', false);

        $user = User::factory()->create();
        $this->createLoginLog($user);

        $result = $this->detector->detect($user, $this->createRequest('198.51.100.1'));

        $this->assertEmpty($result);
    }

    public function test_first_login_returns_no_anomalies(): void
    {
        $user = User::factory()->create();
        $request = $this->createRequest();

        $result = $this->detector->detect($user, $request);

        $this->assertEmpty($result);
    }

    // ========================================
    // IP変化検知
    // ========================================

    public function test_detects_ip_change(): void
    {
        $user = User::factory()->create();
        $this->createLoginLog($user, ['ip_address' => '203.0.113.1']);

        $request = $this->createRequest('198.51.100.1');
        $result = $this->detector->detect($user, $request);

        $ipAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginIpChange);
        $this->assertNotNull($ipAnomaly);
        $this->assertEquals('203.0.113.1', $ipAnomaly['metadata']['previous_ip']);
        $this->assertEquals('198.51.100.1', $ipAnomaly['metadata']['current_ip']);
    }

    public function test_no_detection_when_ip_unchanged(): void
    {
        $user = User::factory()->create();
        $this->createLoginLog($user, ['ip_address' => '203.0.113.1']);

        $request = $this->createRequest('203.0.113.1');
        $result = $this->detector->detect($user, $request);

        $ipAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginIpChange);
        $this->assertNull($ipAnomaly);
    }

    public function test_ignores_private_ip_changes_when_configured(): void
    {
        Config::set('login_anomaly.ip_change.ignore_private_ip_changes', true);

        $user = User::factory()->create();
        $this->createLoginLog($user, ['ip_address' => '192.168.1.1']);

        $request = $this->createRequest('10.0.0.1');
        $result = $this->detector->detect($user, $request);

        $ipAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginIpChange);
        $this->assertNull($ipAnomaly);
    }

    public function test_detects_private_to_public_ip_change(): void
    {
        Config::set('login_anomaly.ip_change.ignore_private_ip_changes', true);

        $user = User::factory()->create();
        $this->createLoginLog($user, ['ip_address' => '192.168.1.1']);

        $request = $this->createRequest('203.0.113.1');
        $result = $this->detector->detect($user, $request);

        $ipAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginIpChange);
        $this->assertNotNull($ipAnomaly);
    }

    public function test_ip_change_skipped_when_disabled(): void
    {
        Config::set('login_anomaly.ip_change.enabled', false);

        $user = User::factory()->create();
        $this->createLoginLog($user, ['ip_address' => '203.0.113.1']);

        $request = $this->createRequest('198.51.100.1');
        $result = $this->detector->detect($user, $request);

        $ipAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginIpChange);
        $this->assertNull($ipAnomaly);
    }

    // ========================================
    // 高頻度ログイン検知
    // ========================================

    public function test_detects_high_frequency_login(): void
    {
        Config::set('login_anomaly.frequency.window_minutes', 60);
        Config::set('login_anomaly.frequency.threshold', 3);

        $user = User::factory()->create();
        // 過去のログイン2回 + 今回1回 = 3 >= threshold(3) で検知
        $this->createLoginLog($user, ['created_at' => now()->subMinutes(10)]);
        $this->createLoginLog($user, ['created_at' => now()->subMinutes(5)]);

        $request = $this->createRequest();
        $result = $this->detector->detect($user, $request);

        $freqAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginFrequency);
        $this->assertNotNull($freqAnomaly);
        $this->assertEquals(3, $freqAnomaly['metadata']['count']);
        $this->assertEquals(60, $freqAnomaly['metadata']['window_minutes']);
        $this->assertEquals(3, $freqAnomaly['metadata']['threshold']);
    }

    public function test_no_detection_when_below_frequency_threshold(): void
    {
        Config::set('login_anomaly.frequency.window_minutes', 60);
        Config::set('login_anomaly.frequency.threshold', 3);

        $user = User::factory()->create();
        // 過去1回 + 今回1回 = 2 < threshold(3) で検知なし
        $this->createLoginLog($user, ['created_at' => now()->subMinutes(10)]);

        $request = $this->createRequest();
        $result = $this->detector->detect($user, $request);

        $freqAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginFrequency);
        $this->assertNull($freqAnomaly);
    }

    public function test_frequency_ignores_logins_outside_window(): void
    {
        Config::set('login_anomaly.frequency.window_minutes', 60);
        Config::set('login_anomaly.frequency.threshold', 3);

        $user = User::factory()->create();
        // ウィンドウ外のログイン2回（カウントされない） + 今回1回 = 1 < 3
        $this->createLoginLog($user, ['created_at' => now()->subMinutes(120)]);
        $this->createLoginLog($user, ['created_at' => now()->subMinutes(90)]);

        $request = $this->createRequest();
        $result = $this->detector->detect($user, $request);

        $freqAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginFrequency);
        $this->assertNull($freqAnomaly);
    }

    public function test_frequency_skipped_when_disabled(): void
    {
        Config::set('login_anomaly.frequency.enabled', false);
        Config::set('login_anomaly.frequency.threshold', 2);

        $user = User::factory()->create();
        $this->createLoginLog($user, ['created_at' => now()->subMinutes(5)]);
        $this->createLoginLog($user, ['created_at' => now()->subMinutes(3)]);

        $request = $this->createRequest();
        $result = $this->detector->detect($user, $request);

        $freqAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginFrequency);
        $this->assertNull($freqAnomaly);
    }

    // ========================================
    // 新規デバイス検知
    // ========================================

    public function test_detects_new_device(): void
    {
        $user = User::factory()->create();
        $this->createLoginLog($user, [
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);

        $firefoxUa = 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0';
        $request = $this->createRequest('203.0.113.1', $firefoxUa);
        $result = $this->detector->detect($user, $request);

        $deviceAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginNewDevice);
        $this->assertNotNull($deviceAnomaly);
        $this->assertEquals('Firefox/Linux', $deviceAnomaly['metadata']['current_device']);
    }

    public function test_no_detection_when_known_device(): void
    {
        $user = User::factory()->create();
        $chromeUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $this->createLoginLog($user, ['user_agent' => $chromeUa]);

        // 同じブラウザ/OSの異なるバージョン
        $chromeUaNewVersion = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';
        $request = $this->createRequest('203.0.113.1', $chromeUaNewVersion);
        $result = $this->detector->detect($user, $request);

        $deviceAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginNewDevice);
        $this->assertNull($deviceAnomaly);
    }

    public function test_known_devices_metadata_is_unique_after_normalization(): void
    {
        Config::set('login_anomaly.frequency.enabled', false);

        $user = User::factory()->create();
        $chromeUaV120 = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $chromeUaV121 = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';

        $this->createLoginLog($user, ['user_agent' => $chromeUaV120]);
        $this->createLoginLog($user, ['user_agent' => $chromeUaV121]);

        $firefoxUa = 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0';
        $result = $this->detector->detect($user, $this->createRequest('203.0.113.1', $firefoxUa));

        $deviceAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginNewDevice);
        $this->assertNotNull($deviceAnomaly);
        $this->assertSame(['Chrome/Windows'], $deviceAnomaly['metadata']['known_devices']);
    }

    public function test_new_device_detection_only_uses_most_recent_distinct_user_agents_up_to_configured_cap(): void
    {
        Config::set('login_anomaly.frequency.enabled', false);
        Config::set('login_anomaly.new_device.max_known_user_agents', 2);

        $user = User::factory()->create();
        $oldChromeUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $firefoxUa = 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0';
        $edgeUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0';

        $this->createLoginLog($user, ['user_agent' => $oldChromeUa]);
        $this->createLoginLog($user, ['user_agent' => $firefoxUa]);
        $this->createLoginLog($user, ['user_agent' => $edgeUa]);

        $chromeUaNewVersion = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';
        $result = $this->detector->detect($user, $this->createRequest('203.0.113.1', $chromeUaNewVersion));

        $deviceAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginNewDevice);
        $this->assertNotNull($deviceAnomaly);
        $this->assertSame('Chrome/Windows', $deviceAnomaly['metadata']['current_device']);
        $this->assertSame(['Edge/Windows', 'Firefox/Linux'], $deviceAnomaly['metadata']['known_devices']);
    }

    public function test_new_device_history_cap_is_configurable(): void
    {
        Config::set('login_anomaly.frequency.enabled', false);
        Config::set('login_anomaly.new_device.max_known_user_agents', 3);

        $user = User::factory()->create();
        $oldChromeUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $firefoxUa = 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0';
        $edgeUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0';

        $this->createLoginLog($user, ['user_agent' => $oldChromeUa]);
        $this->createLoginLog($user, ['user_agent' => $firefoxUa]);
        $this->createLoginLog($user, ['user_agent' => $edgeUa]);

        $chromeUaNewVersion = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';
        $result = $this->detector->detect($user, $this->createRequest('203.0.113.1', $chromeUaNewVersion));

        $deviceAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginNewDevice);
        $this->assertNull($deviceAnomaly);
    }

    public function test_new_device_first_login_skipped(): void
    {
        $user = User::factory()->create();

        $request = $this->createRequest();
        $result = $this->detector->detect($user, $request);

        $deviceAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginNewDevice);
        $this->assertNull($deviceAnomaly);
    }

    public function test_new_device_skipped_when_disabled(): void
    {
        Config::set('login_anomaly.new_device.enabled', false);

        $user = User::factory()->create();
        $this->createLoginLog($user, [
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);

        $firefoxUa = 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0';
        $request = $this->createRequest('203.0.113.1', $firefoxUa);
        $result = $this->detector->detect($user, $request);

        $deviceAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginNewDevice);
        $this->assertNull($deviceAnomaly);
    }

    // ========================================
    // UA正規化
    // ========================================

    public function test_normalize_user_agent_chrome_windows(): void
    {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $this->assertEquals('Chrome/Windows', $this->detector->normalizeUserAgent($ua));
    }

    public function test_normalize_user_agent_safari_ios(): void
    {
        $ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
        $this->assertEquals('Safari/iOS', $this->detector->normalizeUserAgent($ua));
    }

    public function test_normalize_user_agent_firefox_linux(): void
    {
        $ua = 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0';
        $this->assertEquals('Firefox/Linux', $this->detector->normalizeUserAgent($ua));
    }

    public function test_normalize_user_agent_edge_windows(): void
    {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0';
        $this->assertEquals('Edge/Windows', $this->detector->normalizeUserAgent($ua));
    }

    public function test_normalize_user_agent_chrome_macos(): void
    {
        $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $this->assertEquals('Chrome/macOS', $this->detector->normalizeUserAgent($ua));
    }

    public function test_normalize_user_agent_chrome_android(): void
    {
        $ua = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';
        $this->assertEquals('Chrome/Android', $this->detector->normalizeUserAgent($ua));
    }

    public function test_normalize_user_agent_unknown(): void
    {
        $ua = 'SomeUnknownBot/1.0';
        $this->assertEquals('Unknown/Unknown', $this->detector->normalizeUserAgent($ua));
    }

    // ========================================
    // 複数異常の同時検知
    // ========================================

    public function test_detects_multiple_anomalies_simultaneously(): void
    {
        Config::set('login_anomaly.frequency.window_minutes', 60);
        Config::set('login_anomaly.frequency.threshold', 3);

        $user = User::factory()->create();
        $chromeUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

        // 同一IPで2回ログイン
        $this->createLoginLog($user, [
            'ip_address' => '203.0.113.1',
            'user_agent' => $chromeUa,
            'created_at' => now()->subMinutes(10),
        ]);
        $this->createLoginLog($user, [
            'ip_address' => '203.0.113.1',
            'user_agent' => $chromeUa,
            'created_at' => now()->subMinutes(5),
        ]);

        // 異なるIP + 異なるデバイスでリクエスト → IP変化 + 新規デバイス + 高頻度
        $firefoxUa = 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0';
        $request = $this->createRequest('198.51.100.1', $firefoxUa);
        $result = $this->detector->detect($user, $request);

        $types = collect($result)->pluck('type')->all();
        $this->assertContains(AuditAction::SuspiciousLoginIpChange, $types);
        $this->assertContains(AuditAction::SuspiciousLoginFrequency, $types);
        $this->assertContains(AuditAction::SuspiciousLoginNewDevice, $types);
        $this->assertCount(3, $result);
    }

    // ========================================
    // 通知クールダウン
    // ========================================

    public function test_should_notify_returns_true_when_no_cooldown(): void
    {
        $this->assertTrue($this->detector->shouldNotify(1, 'auth.suspicious_login.ip_change'));
    }

    public function test_should_notify_returns_false_during_cooldown(): void
    {
        $this->detector->markNotified(1, 'auth.suspicious_login.ip_change');

        $this->assertFalse($this->detector->shouldNotify(1, 'auth.suspicious_login.ip_change'));
    }

    public function test_cooldown_uses_configured_duration(): void
    {
        Config::set('login_anomaly.notification_cooldown_minutes', 30);

        $this->detector->markNotified(1, 'auth.suspicious_login.ip_change');

        $this->assertTrue(
            Cache::has('login_anomaly_notification:1:auth.suspicious_login.ip_change')
        );
    }

    public function test_detection_result_includes_should_notify_flag(): void
    {
        $user = User::factory()->create();
        $this->createLoginLog($user, ['ip_address' => '203.0.113.1']);

        // クールダウン設定
        $this->detector->markNotified($user->id, AuditAction::SuspiciousLoginIpChange->value);

        $request = $this->createRequest('198.51.100.1');
        $result = $this->detector->detect($user, $request);

        $ipAnomaly = collect($result)->firstWhere('type', AuditAction::SuspiciousLoginIpChange);
        $this->assertNotNull($ipAnomaly);
        $this->assertFalse($ipAnomaly['should_notify']);
    }
}

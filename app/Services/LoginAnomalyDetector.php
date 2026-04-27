<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LoginAnomalyDetector
{
    /**
     * 3種類の異常ログインパターンをまとめて検知する。
     *
     * @return array<int, array{type: AuditAction, metadata: array<string, mixed>}>
     */
    public function detect(User $user, Request $request): array
    {
        if (! config('login_anomaly.enabled')) {
            return [];
        }

        $anomalies = [];

        $detectors = [
            'ip_change' => fn () => $this->detectIpChange($user, $request),
            'frequency' => fn () => $this->detectFrequency($user, $request),
            'new_device' => fn () => $this->detectNewDevice($user, $request),
        ];

        foreach ($detectors as $key => $detector) {
            if (! config("login_anomaly.{$key}.enabled")) {
                continue;
            }

            $result = $detector();
            if ($result !== null) {
                $anomalies[] = $result;
            }
        }

        return $anomalies;
    }

    /**
     * IP変化を検知する。
     * 直前のログインとIPが異なる場合に検知。
     *
     * @return array{type: AuditAction, metadata: array<string, mixed>}|null
     */
    protected function detectIpChange(User $user, Request $request): ?array
    {
        $lastLogin = AuditLog::where('action', AuditAction::Login->value)
            ->where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->latest('id')
            ->first();

        if ($lastLogin === null) {
            return null;
        }

        $previousIp = $lastLogin->ip_address;
        $currentIp = $request->ip();

        if ($previousIp === $currentIp) {
            return null;
        }

        if (config('login_anomaly.ip_change.ignore_private_ip_changes')
            && $this->isPrivateIp($previousIp) && $this->isPrivateIp($currentIp)) {
            return null;
        }

        return [
            'type' => AuditAction::SuspiciousLoginIpChange,
            'metadata' => [
                'previous_ip' => $previousIp,
                'current_ip' => $currentIp,
            ],
        ];
    }

    /**
     * 高頻度ログインを検知する。
     * 過去N分以内のログイン回数が閾値以上の場合に検知。
     *
     * @return array{type: AuditAction, metadata: array<string, mixed>}|null
     */
    protected function detectFrequency(User $user, Request $request): ?array
    {
        $window = (int) config('login_anomaly.frequency.window_minutes');
        $threshold = (int) config('login_anomaly.frequency.threshold');

        $count = AuditLog::where('action', AuditAction::Login->value)
            ->where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes($window))
            ->count();

        $totalCount = $count + 1;

        if ($totalCount < $threshold) {
            return null;
        }

        return [
            'type' => AuditAction::SuspiciousLoginFrequency,
            'metadata' => [
                'count' => $totalCount,
                'window_minutes' => $window,
                'threshold' => $threshold,
            ],
        ];
    }

    /**
     * 新規デバイスを検知する。
     * 過去のログインに存在しないUser-Agentの場合に検知。
     *
     * @return array{type: AuditAction, metadata: array<string, mixed>}|null
     */
    protected function detectNewDevice(User $user, Request $request): ?array
    {
        $currentUa = $this->normalizeUserAgent($request->userAgent() ?? '');
        $previousUas = $this->getKnownDevices($user);

        if ($previousUas->isEmpty()) {
            return null;
        }

        if ($previousUas->contains($currentUa)) {
            return null;
        }

        return [
            'type' => AuditAction::SuspiciousLoginNewDevice,
            'metadata' => [
                'current_device' => $currentUa,
                'known_devices' => $previousUas->all(),
            ],
        ];
    }

    /**
     * 直近に利用された既知デバイス一覧を取得する。
     *
     * @return Collection<int, string>
     */
    protected function getKnownDevices(User $user): Collection
    {
        $maxKnownUserAgents = max((int) config('login_anomaly.new_device.max_known_user_agents', 200), 1);

        $latestDistinctUserAgents = AuditLog::query()
            ->selectRaw('user_agent, MAX(id) as latest_id')
            ->where('action', AuditAction::Login->value)
            ->where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->whereNotNull('user_agent')
            ->groupBy('user_agent');

        return DB::query()
            ->fromSub($latestDistinctUserAgents->toBase(), 'recent_user_agents')
            ->orderByDesc('latest_id')
            ->limit($maxKnownUserAgents)
            ->pluck('user_agent')
            ->map(fn (string $ua) => $this->normalizeUserAgent($ua))
            ->unique()
            ->values();
    }

    /**
     * User-Agentを正規化する。
     * ブラウザファミリーとOSのみ抽出し、バージョンは含めない。
     */
    public function normalizeUserAgent(string $ua): string
    {
        $browser = 'Unknown';
        $os = 'Unknown';

        // OS判定
        if (preg_match('/iPhone|iPad|iPod/', $ua)) {
            $os = 'iOS';
        } elseif (preg_match('/Android/', $ua)) {
            $os = 'Android';
        } elseif (preg_match('/Windows/', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS/', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/', $ua)) {
            $os = 'Linux';
        }

        // ブラウザ判定（順序が重要: Edge/OPR/Chromeの順で判定）
        if (preg_match('/Edg(e|\/)\d/', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/OPR\//', $ua)) {
            $browser = 'Opera';
        } elseif (preg_match('/Chrome\//', $ua) && ! preg_match('/Chromium/', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari\//', $ua) && ! preg_match('/Chrome/', $ua)) {
            $browser = 'Safari';
        } elseif (preg_match('/Firefox\//', $ua)) {
            $browser = 'Firefox';
        }

        return "{$browser}/{$os}";
    }

    /**
     * プライベートIPかどうかを判定する。
     */
    protected function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}

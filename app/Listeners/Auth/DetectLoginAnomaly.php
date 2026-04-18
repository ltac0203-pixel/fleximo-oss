<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Models\User;
use App\Notifications\SuspiciousLoginNotification;
use App\Services\AuditLogger;
use App\Services\LoginAnomalyDetector;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class DetectLoginAnomaly
{
    public function __construct(
        private readonly LoginAnomalyDetector $detector,
    ) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $user = $event->user;
        $request = request();

        try {
            $anomalies = $this->detector->detect($user, $request);

            if (empty($anomalies)) {
                return;
            }

            foreach ($anomalies as $anomaly) {
                AuditLogger::log(
                    action: $anomaly['type'],
                    target: $user,
                    changes: [
                        'metadata' => $anomaly['metadata'],
                    ],
                    tenantId: $user->getTenantId(),
                );
            }

            $notifiable = array_filter($anomalies, fn (array $a) => $a['should_notify']);

            if (! empty($notifiable)) {
                $this->detector->markNotifiedAll($user->id, $notifiable);

                $user->notify(new SuspiciousLoginNotification(
                    anomalies: $notifiable,
                    ipAddress: $request->ip() ?? 'unknown',
                    userAgent: $request->userAgent() ?? 'unknown',
                    loginAt: now()->format('Y-m-d H:i:s'),
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Login anomaly detection failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

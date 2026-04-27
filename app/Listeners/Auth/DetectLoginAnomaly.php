<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Models\User;
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
        } catch (\Throwable $e) {
            Log::error('Login anomaly detection failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

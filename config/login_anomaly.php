<?php

declare(strict_types=1);

return [
    'enabled' => env('LOGIN_ANOMALY_DETECTION_ENABLED', true),

    'ip_change' => [
        'enabled' => env('LOGIN_ANOMALY_IP_CHANGE_ENABLED', true),
        'ignore_private_ip_changes' => true,
    ],

    'frequency' => [
        'enabled' => env('LOGIN_ANOMALY_FREQUENCY_ENABLED', true),
        'window_minutes' => (int) env('LOGIN_ANOMALY_FREQUENCY_WINDOW', 60),
        'threshold' => (int) env('LOGIN_ANOMALY_FREQUENCY_THRESHOLD', 3),
    ],

    'new_device' => [
        'enabled' => env('LOGIN_ANOMALY_NEW_DEVICE_ENABLED', true),
        'max_known_user_agents' => (int) env('LOGIN_ANOMALY_NEW_DEVICE_MAX_KNOWN_USER_AGENTS', 200),
    ],

    'notification_cooldown_minutes' => (int) env('LOGIN_ANOMALY_NOTIFICATION_COOLDOWN', 60),
];

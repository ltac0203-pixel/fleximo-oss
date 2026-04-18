<?php

declare(strict_types=1);

return [
    'ready_auto_complete_seconds' => (int) env('KDS_READY_AUTO_COMPLETE_SECONDS', 300),
    'ready_auto_complete_fallback_minutes' => (int) env('KDS_READY_AUTO_COMPLETE_FALLBACK_MINUTES', 10),
    'warning_threshold_minutes' => (int) env('KDS_WARNING_THRESHOLD_MINUTES', 15),
];

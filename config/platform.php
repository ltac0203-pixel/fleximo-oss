<?php

declare(strict_types=1);

return [
    // テナント別手数料率が未設定の場合に適用する既定値（6.00%）
    'default_fee_rate_bps' => (int) env('PLATFORM_DEFAULT_FEE_RATE_BPS', 600),
];

<?php

declare(strict_types=1);

$productionOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
)));

if ($productionOrigins === []) {
    $appUrl = rtrim((string) env('APP_URL', 'https://example.com'), '/');
    $productionOrigins = [$appUrl];
}

$defaultAllowedOrigins = env('APP_ENV', 'production') === 'production'
    ? $productionOrigins
    : array_values(array_unique(array_merge([
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ], $productionOrigins)));

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | API と Sanctum CSRF エンドポイントに対する CORS 設定。
    | 本番で許可するオリジンは CORS_ALLOWED_ORIGINS（カンマ区切り）で指定する。
    | 未指定時は APP_URL 単独を許可。開発環境ではローカルオリジンも自動許可。
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // セキュリティ上の理由で、許可オリジンは環境変数で上書きしない。
    'allowed_origins' => $defaultAllowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Accept',
        'Authorization',
        'X-XSRF-TOKEN',
        'X-Requested-With',
        'Idempotency-Key',
    ],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,
];

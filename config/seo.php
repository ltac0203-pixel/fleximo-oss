<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | サイト共通の SEO 設定
    |--------------------------------------------------------------------------
    |
    | base_url のデフォルトは APP_URL を使用。OSS版のデフォルトは example.com。
    | 公開メールアドレスは .env の SEO_* キーで上書きできます。
    |
    */

    'site' => [
        'name' => env('APP_NAME', 'Fleximo'),
        'base_url' => env('APP_URL', 'https://example.com'),
        'default_image_path' => env('SEO_DEFAULT_IMAGE_PATH', '/og-image.svg'),
        'logo_path' => env('SEO_LOGO_PATH', '/logo.png'),
        'contact_email' => env('SEO_CONTACT_EMAIL', 'contact@example.com'),
        'support_email' => env('SEO_SUPPORT_EMAIL', 'support@example.com'),
        'description' => env(
            'SEO_DEFAULT_DESCRIPTION',
            '日本の飲食店向けオープンソース・マルチテナント モバイルオーダー。QRコード注文、PayPay・クレジットカード決済、KDSで受注から受け取りまでを効率化します。学食・フードコート・小規模ECにも対応。'
        ),
    ],

    'pages' => [],
];

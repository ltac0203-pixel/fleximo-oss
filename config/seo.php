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

    'pages' => [
        'welcome' => [
            'default' => [
                'title' => env('SEO_WELCOME_TITLE', 'モバイルオーダー OSS | PayPay対応・並ばずスマホで注文・決済'),
                'description' => env(
                    'SEO_WELCOME_DESCRIPTION',
                    'Fleximoは日本の飲食店向けオープンソース・マルチテナント モバイルオーダー。QRコード注文とPayPay / クレジットカード決済で、ピーク時の行列を解消。アプリ不要・30秒で登録・完全無料。'
                ),
                'keywords' => env(
                    'SEO_WELCOME_KEYWORDS',
                    'モバイルオーダー,OSS,オープンソース,マルチテナント,QRコード注文,PayPay,キャッシュレス決済,飲食店,学食,フードコート,行列解消'
                ),
                'ogType' => 'website',
            ],
            'locales' => [
                // 例:
                // 'en' => [
                //     'title' => 'Campus Mobile Ordering | Order and Pay Without Waiting',
                // ],
            ],
        ],
        'for_business' => [
            'default' => [
                'title' => env('SEO_FOR_BUSINESS_TITLE', '飲食店向けモバイルオーダー導入 | 初期費用0円・月額0円・PayPay対応'),
                'description' => env(
                    'SEO_FOR_BUSINESS_DESCRIPTION',
                    '飲食店・学食・フードコート向けマルチテナント モバイルオーダー。初期費用0円・月額0円・専用端末不要。QRコード注文とKDSで回転率40%向上、注文ミス90%削減。PayPay・クレジットカード決済対応。'
                ),
                'keywords' => env(
                    'SEO_FOR_BUSINESS_KEYWORDS',
                    '飲食店,フードコート,学食,モバイルオーダー,導入,初期費用無料,KDS,キッチンディスプレイ,回転率向上,PayPay,キャッシュレス決済'
                ),
                'ogType' => 'website',
            ],
            'locales' => [
                // 例:
                // 'en' => [
                //     'title' => 'Mobile Ordering for Restaurants | No Initial or Monthly Fees',
                // ],
            ],
        ],
        'tenant_application' => [
            'default' => [
                'title' => env('SEO_TENANT_APPLICATION_TITLE', '加盟店申し込み | モバイルオーダー導入申請 (PayPay対応)'),
                'description' => env(
                    'SEO_TENANT_APPLICATION_DESCRIPTION',
                    'Fleximoの加盟店申し込みページです。飲食店・学食・フードコート向けに、QRコード注文、PayPay・クレジットカード決済、KDSをまとめて導入できます。'
                ),
                'keywords' => env(
                    'SEO_TENANT_APPLICATION_KEYWORDS',
                    '加盟店申し込み,モバイルオーダー,QRコード注文,飲食店,学食,フードコート,KDS,PayPay,キャッシュレス決済'
                ),
                'ogType' => 'website',
            ],
            'locales' => [],
        ],
        'tenant_application_complete' => [
            'default' => [
                'title' => env('SEO_TENANT_APPLICATION_COMPLETE_TITLE', '加盟店申し込み完了'),
                'description' => env(
                    'SEO_TENANT_APPLICATION_COMPLETE_DESCRIPTION',
                    'Fleximoの加盟店申し込み完了ページです。'
                ),
                'ogType' => 'website',
                'noindex' => true,
            ],
            'locales' => [],
        ],
    ],
];

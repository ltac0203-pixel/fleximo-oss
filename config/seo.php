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
            '飲食店・学食向けモバイルオーダープラットフォーム。QRコード注文、キャッシュレス決済、KDSで受注から受け取りまでを効率化します。'
        ),
    ],

    'pages' => [
        'welcome' => [
            'default' => [
                'title' => env('SEO_WELCOME_TITLE', '学食モバイルオーダー | 並ばずスマホで注文・決済'),
                'description' => env(
                    'SEO_WELCOME_DESCRIPTION',
                    'Fleximoは学食・学生食堂向けモバイルオーダーシステム。QRコード注文とキャッシュレス決済で、昼休みの行列を解消。アプリ不要・30秒で登録・完全無料。'
                ),
                'keywords' => env(
                    'SEO_WELCOME_KEYWORDS',
                    '学食,モバイルオーダー,QRコード注文,キャッシュレス決済,学生食堂,PayPay,行列解消'
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
                'title' => env('SEO_FOR_BUSINESS_TITLE', '飲食店向けモバイルオーダー導入 | 初期費用0円・月額0円'),
                'description' => env(
                    'SEO_FOR_BUSINESS_DESCRIPTION',
                    '飲食店・学食向けモバイルオーダーシステム。初期費用0円・月額0円・専用端末不要。QRコード注文とKDSで回転率40%向上、注文ミス90%削減。PayPay・クレジットカード決済対応。'
                ),
                'keywords' => env(
                    'SEO_FOR_BUSINESS_KEYWORDS',
                    '飲食店,モバイルオーダー,導入,初期費用無料,KDS,キッチンディスプレイ,回転率向上,決済,PayPay'
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
        'contact' => [
            'default' => [
                'title' => env('SEO_CONTACT_TITLE', 'お問い合わせ | 導入相談・サポート窓口'),
                'description' => env(
                    'SEO_CONTACT_DESCRIPTION',
                    'Fleximoへの導入相談、モバイルオーダー運用に関するお問い合わせ、サポート依頼を受け付けています。飲食店・学食向けの導入相談もこちらからご連絡ください。'
                ),
                'keywords' => env(
                    'SEO_CONTACT_KEYWORDS',
                    'Fleximo,お問い合わせ,導入相談,サポート,モバイルオーダー,飲食店DX'
                ),
                'ogType' => 'website',
            ],
            'locales' => [],
        ],
        'tenant_application' => [
            'default' => [
                'title' => env('SEO_TENANT_APPLICATION_TITLE', '加盟店申し込み | モバイルオーダー導入申請'),
                'description' => env(
                    'SEO_TENANT_APPLICATION_DESCRIPTION',
                    'Fleximoの加盟店申し込みページです。飲食店・学食・フードコート向けに、QRコード注文、キャッシュレス決済、KDSをまとめて導入できます。'
                ),
                'keywords' => env(
                    'SEO_TENANT_APPLICATION_KEYWORDS',
                    '加盟店申し込み,モバイルオーダー,QRコード注文,飲食店,学食,KDS,キャッシュレス決済'
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

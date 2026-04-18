<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | デフォルトメーラー
    |--------------------------------------------------------------------------
    |
    | このオプションはメールメッセージの送信時に使用されるデフォルトメーラーを
    | 制御します。別のメーラーが明示的に指定されていない限り、全てのメール
    | メッセージはこのメーラーで送信されます。追加のメーラーは「mailers」配列
    | 内で設定できます。各メーラータイプの例が提供されています。
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | メーラー設定
    |--------------------------------------------------------------------------
    |
    | ここではアプリケーションで使用される全てのメーラーおよびそれぞれの
    | 設定を行えます。いくつかの例が既に設定されており、アプリケーションの
    | 要件に応じて自由に追加設定できます。
    |
    | Laravel は様々なメール「トランスポート」ドライバーをサポートしており、
    | メール配信時に使用できます。以下で使用するメーラーを指定してください。
    | 必要に応じて追加のメーラーも追加できます。
    |
    | サポート: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |          "postmark", "resend", "log", "array",
    |          "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
            'verify_peer' => false,
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | グローバル送信元アドレス
    |--------------------------------------------------------------------------
    |
    | アプリケーションから送信される全てのメールを同じアドレスから送信したい
    | 場合があります。ここではアプリケーションから送信される全てのメールで
    | グローバルに使用される名前とアドレスを指定できます。
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    'from_addresses' => [
        'no_reply' => [
            'address' => env('MAIL_FROM_ADDRESS_NO_REPLY', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
            'name' => env('MAIL_FROM_NAME_NO_REPLY', env('MAIL_FROM_NAME', 'Example')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 管理者メールアドレス
    |--------------------------------------------------------------------------
    |
    | 管理者への通知メール（テナント申し込み通知など）の送信先アドレスです。
    |
    */

    'admin_email' => env('MAIL_ADMIN_ADDRESS'),

];

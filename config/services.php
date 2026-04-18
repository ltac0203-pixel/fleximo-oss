<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | サードパーティサービス
    |--------------------------------------------------------------------------
    |
    | このファイルは、Mailgun、Postmark、AWSなどのサードパーティサービスの
    | 認証情報を保存するためのものです。このファイルは、この種の情報の
    | 事実上の場所を提供し、パッケージがさまざまなサービス認証情報を
    | 見つけるための従来型のファイルを持つことを可能にします。
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];

<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | デフォルトキュー接続名
    |--------------------------------------------------------------------------
    |
    | Laravelのキューは、単一の統一されたAPIを介してさまざまなバックエンドを
    | サポートし、各バックエンドに同じ構文でアクセスできます。
    | デフォルトのキュー接続は以下で定義されています。
    |
    */

    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | キュー接続
    |--------------------------------------------------------------------------
    |
    | ここでは、アプリケーションが使用するすべてのキューバックエンドの
    | 接続オプションを設定できます。Laravelがサポートする各バックエンドの
    | 設定例が提供されています。さらに追加することもできます。
    |
    | ドライバ: "sync", "database", "beanstalkd", "sqs", "redis",
    |          "deferred", "background", "failover", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 120),
            'after_commit' => true,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

        'deferred' => [
            'driver' => 'deferred',
        ],

        'background' => [
            'driver' => 'background',
        ],

        'failover' => [
            'driver' => 'failover',
            'connections' => [
                'database',
                'deferred',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | ジョブバッチ処理
    |--------------------------------------------------------------------------
    |
    | 以下のオプションは、ジョブバッチ処理情報を保存するデータベースと
    | テーブルを設定します。これらのオプションは、アプリケーションで定義された
    | 任意のデータベース接続とテーブルに更新できます。
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | 失敗したキュージョブ
    |--------------------------------------------------------------------------
    |
    | これらのオプションは、失敗したキュージョブのログ記録の動作を設定し、
    | 失敗したジョブの保存方法と保存場所を制御できます。Laravelは、
    | 失敗したジョブをシンプルなファイルまたはデータベースに保存する
    | サポートを提供しています。
    |
    | サポートされているドライバ: "database-uuids", "dynamodb", "file", "null"
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];

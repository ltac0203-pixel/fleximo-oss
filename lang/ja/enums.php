<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enum 表示ラベル
    |--------------------------------------------------------------------------
    |
    | App\Enums 配下の Enum::label() メソッドから __() 経由で参照される。
    | フロントエンド側にも同等のラベル定義（resources/js/constants/orderStatus.ts 等）
    | があるが、サーバー側でラベル文字列を返すフロー（API レスポンスや Blade 出力）
    | では本ファイルが真実の源となる。
    |
    */

    'order_status' => [
        'pending_payment' => '決済待ち',
        'paid' => '決済完了',
        'accepted' => '受付済み',
        'in_progress' => '調理中',
        'ready' => '準備完了',
        'completed' => '完了',
        'cancelled' => 'キャンセル',
        'payment_failed' => '決済失敗',
        'refunded' => '返金済み',
    ],

    'payment_status' => [
        'pending' => '決済待ち',
        'processing' => '処理中',
        'completed' => '完了',
        'failed' => '失敗',
    ],

    'payment_method' => [
        'card' => 'クレジットカード',
        'paypay' => 'PayPay',
    ],

];

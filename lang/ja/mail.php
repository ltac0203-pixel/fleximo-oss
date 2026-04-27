<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | メール文言
    |--------------------------------------------------------------------------
    |
    | 通知メール・トランザクションメールで使用される件名・本文。
    |
    */

    'verify_email' => [
        'subject' => 'メールアドレスの確認',
        'greeting' => 'いつもご利用ありがとうございます。',
        'line_intro' => '以下のボタンをクリックして、メールアドレスの認証を完了してください。',
        'action' => 'メールアドレスを認証する',
        'line_expire' => 'このリンクは:minutes分間有効です。有効期限が切れた場合は、再度認証メールを送信してください。',
        'line_disclaimer' => 'このメールに心当たりがない場合は、対応は不要です。',
        'salutation' => '— Fleximo',
    ],

    'order_completed' => [
        'subject' => '[Fleximo] ご注文が完了しました',
        'html' => [
            'title' => 'ご注文完了のお知らせ',
            'heading' => 'ご注文が完了しました',
            'greeting_format' => ':name 様',
            'intro_html' => 'この度はご注文いただきありがとうございます。<br>:tenant でのご注文が完了しました。',
            'label_order_number' => '注文番号',
            'label_tenant' => '店舗名',
            'label_completed_at' => '完了日時',
            'date_format' => 'Y年m月d日 H:i',
            'heading_items' => '注文内容',
            'col_item_name' => '商品名',
            'col_quantity' => '数量',
            'col_subtotal' => '小計',
            'options_separator' => '、',
            'currency_symbol' => '&yen;',
            'label_total' => '合計',
            'outro_html' => 'またのご利用をお待ちしております。<br>Fleximo をよろしくお願いいたします。',
            'footer_disclaimer_html' => 'このメールは Fleximo から自動送信されました。<br>お心当たりがない場合は、このメールを破棄してください。',
        ],
    ],

    'order_cancelled' => [
        'subject' => '[Fleximo] ご注文がキャンセルされました',
        'html' => [
            'title' => 'ご注文キャンセルのお知らせ',
            'heading' => 'ご注文がキャンセルされました',
            'greeting_format' => ':name 様',
            'intro_html' => ':tenant でのご注文がキャンセルされましたのでお知らせいたします。',
            'label_order_number' => '注文番号',
            'label_tenant' => '店舗名',
            'label_cancelled_at' => 'キャンセル日時',
            'date_format' => 'Y年m月d日 H:i',
            'heading_items' => '注文内容',
            'col_item_name' => '商品名',
            'col_quantity' => '数量',
            'col_subtotal' => '小計',
            'options_separator' => '、',
            'currency_symbol' => '&yen;',
            'label_total' => '合計',
            'refund_label' => '返金について：',
            'refund_text' => 'クレジットカードでお支払いの場合、返金処理が完了するまでに数日かかる場合がございます。ご不明な点がございましたら、お気軽にお問い合わせください。',
            'outro_html' => 'ご不明な点がございましたら、お気軽にお問い合わせください。<br>Fleximo をよろしくお願いいたします。',
            'footer_disclaimer_html' => 'このメールは Fleximo から自動送信されました。<br>お心当たりがない場合は、このメールを破棄してください。',
        ],
    ],

];

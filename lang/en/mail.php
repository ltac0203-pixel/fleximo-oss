<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Mail strings
    |--------------------------------------------------------------------------
    |
    | Subjects and body lines used by notification and transactional emails.
    |
    */

    'verify_email' => [
        'subject' => 'Verify your email address',
        'greeting' => 'Thank you for using Fleximo.',
        'line_intro' => 'Please click the button below to verify your email address.',
        'action' => 'Verify Email Address',
        'line_expire' => 'This link will expire in :minutes minutes. If it expires, request a new verification email.',
        'line_disclaimer' => 'If you did not request this, no action is required.',
        'salutation' => '— Fleximo',
    ],

    'order_completed' => [
        'subject' => '[Fleximo] Your order has been placed',
        'html' => [
            'title' => 'Order confirmation',
            'heading' => 'Your order has been placed',
            'greeting_format' => 'Hi :name,',
            'intro_html' => 'Thank you for your order.<br>Your order at :tenant has been received.',
            'label_order_number' => 'Order number',
            'label_tenant' => 'Restaurant',
            'label_completed_at' => 'Placed at',
            'date_format' => 'M j, Y, H:i',
            'heading_items' => 'Order details',
            'col_item_name' => 'Item',
            'col_quantity' => 'Qty',
            'col_subtotal' => 'Subtotal',
            'options_separator' => ', ',
            'currency_symbol' => '&yen;',
            'label_total' => 'Total',
            'outro_html' => 'We hope to serve you again soon.<br>— The Fleximo team',
            'footer_disclaimer_html' => 'This email was sent automatically by Fleximo.<br>If you did not place this order, please disregard this message.',
        ],
    ],

    'order_cancelled' => [
        'subject' => '[Fleximo] Your order has been cancelled',
        'html' => [
            'title' => 'Order cancellation notice',
            'heading' => 'Your order has been cancelled',
            'greeting_format' => 'Hi :name,',
            'intro_html' => 'We are writing to let you know that your order at :tenant has been cancelled.',
            'label_order_number' => 'Order number',
            'label_tenant' => 'Restaurant',
            'label_cancelled_at' => 'Cancelled at',
            'date_format' => 'M j, Y, H:i',
            'heading_items' => 'Order details',
            'col_item_name' => 'Item',
            'col_quantity' => 'Qty',
            'col_subtotal' => 'Subtotal',
            'options_separator' => ', ',
            'currency_symbol' => '&yen;',
            'label_total' => 'Total',
            'refund_label' => 'About refunds: ',
            'refund_text' => 'If you paid by credit card, please allow a few business days for the refund to appear on your statement. Contact us if you have any questions.',
            'outro_html' => 'If you have any questions, please contact us.<br>— The Fleximo team',
            'footer_disclaimer_html' => 'This email was sent automatically by Fleximo.<br>If you did not place this order, please disregard this message.',
        ],
    ],

];

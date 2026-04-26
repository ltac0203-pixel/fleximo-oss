<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enum display labels
    |--------------------------------------------------------------------------
    |
    | Referenced from Enum::label() methods under App\Enums via __().
    | The frontend keeps mirrored label definitions (e.g.
    | resources/js/constants/orderStatus.ts), but for any flow where the
    | server returns the label string (API responses, Blade output) this
    | file is the source of truth.
    |
    */

    'order_status' => [
        'pending_payment' => 'Awaiting payment',
        'paid' => 'Paid',
        'accepted' => 'Accepted',
        'in_progress' => 'Preparing',
        'ready' => 'Ready',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'payment_failed' => 'Payment failed',
        'refunded' => 'Refunded',
    ],

    'payment_status' => [
        'pending' => 'Awaiting payment',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ],

    'payment_method' => [
        'card' => 'Credit card',
        'paypay' => 'PayPay',
    ],

];

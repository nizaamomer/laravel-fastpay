<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default FastPay Store
    |--------------------------------------------------------------------------
    |
    | The store used when no explicit store is requested. Matches a key in
    | the "stores" array below.
    |
    */
    'default' => env('FASTPAY_STORE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | FastPay Stores
    |--------------------------------------------------------------------------
    |
    | Each store holds its own credentials so a single application can accept
    | payments through multiple FastPay merchant stores. FastPay has no OAuth
    | flow — the store_id/store_password pair authenticates every request.
    |
    | environment: "staging" or "production" — selects the base URLs below.
    | refund_secret_key: found under Store Details inside the Merchant Panel;
    | only needed if you process refunds through the payment gateway API.
    |
    */
    'stores' => [
        'default' => [
            'environment' => env('FASTPAY_ENVIRONMENT', 'staging'),
            'store_id' => env('FASTPAY_STORE_ID'),
            'store_password' => env('FASTPAY_STORE_PASSWORD'),
            'refund_secret_key' => env('FASTPAY_REFUND_SECRET_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoints
    |--------------------------------------------------------------------------
    |
    | pgw: the merchant payment gateway (initiate / validate / refund).
    | qr:  the QR vending API (generate / validate / refund).
    |
    */
    'endpoints' => [
        'staging' => [
            'pgw' => 'https://staging-apigw-merchant.fast-pay.iq',
            'qr' => 'https://staging-qr.fast-pay.iq',
        ],
        'production' => [
            'pgw' => 'https://apigw-merchant.fast-pay.iq',
            'qr' => 'https://qr.fast-pay.iq',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | FastPay currently only supports IQD.
    |
    */
    'currency' => env('FASTPAY_CURRENCY', 'IQD'),

    /*
    |--------------------------------------------------------------------------
    | Redirect & Notification URLs
    |--------------------------------------------------------------------------
    |
    | success_url / cancel_url: where FastPay sends the customer back after
    | the payment outcome. callback_url: where FastPay POSTs the Instant
    | Payment Notification (IPN) on successful payments only. Never trust
    | the IPN body directly — always re-verify with FastpayPayment::validate()
    | before fulfilling an order.
    |
    */
    'success_url' => env('FASTPAY_SUCCESS_URL'),
    'cancel_url' => env('FASTPAY_CANCEL_URL'),
    'callback_url' => env('FASTPAY_CALLBACK_URL'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    |
    | TLS certificate verification is always enabled and cannot be disabled
    | through configuration.
    |
    */
    'http' => [
        'timeout' => (int) env('FASTPAY_HTTP_TIMEOUT', 15),
        'retry_times' => (int) env('FASTPAY_HTTP_RETRY_TIMES', 1),
        'retry_sleep_ms' => (int) env('FASTPAY_HTTP_RETRY_SLEEP_MS', 200),
    ],

];

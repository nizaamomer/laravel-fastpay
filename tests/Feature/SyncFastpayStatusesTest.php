<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Nizaamomer\LaravelFastpay\Enums\PaymentStatus;
use Nizaamomer\LaravelFastpay\Models\FastpayPayment;

it('re-checks pending payments', function () {
    FastpayPayment::query()->create([
        'store' => 'default',
        'order_id' => 'ORDER00123456',
        'amount' => 250,
        'currency' => 'IQD',
        'status' => PaymentStatus::Pending,
    ]);

    Http::fake([
        '*/api/v1/public/pgw/payment/validate' => Http::response([
            'code' => 200,
            'messages' => [],
            'data' => [
                'gw_transaction_id' => 'AXMNP7A004',
                'merchant_order_id' => 'ORDER00123456',
                'received_amount' => '250.00',
                'currency' => 'IQD',
                'customer_name' => 'John Doe',
                'customer_mobile_number' => '+9641000000004',
                'at' => '2023-12-13 15:32:00',
                'status' => 'Success',
                'received_at' => '2023-12-13 15:32:00',
            ],
        ], 200),
    ]);

    $this->artisan('fastpay:sync-statuses')->assertExitCode(0);

    expect(FastpayPayment::query()->where('order_id', 'ORDER00123456')->value('status'))
        ->toBe(PaymentStatus::Success);
});

it('does not fail the command when an order is still genuinely unpaid', function () {
    FastpayPayment::query()->create([
        'store' => 'default',
        'order_id' => 'ORDER00123456',
        'amount' => 250,
        'currency' => 'IQD',
        'status' => PaymentStatus::Pending,
    ]);

    Http::fake([
        '*/api/v1/public/pgw/payment/validate' => Http::response([
            'code' => 404,
            'messages' => ['Sorry! No transaction has been found against your Order ID.'],
            'data' => null,
        ], 200),
    ]);

    $this->artisan('fastpay:sync-statuses')->assertExitCode(0);

    expect(FastpayPayment::query()->where('order_id', 'ORDER00123456')->value('status'))
        ->toBe(PaymentStatus::Pending);
});

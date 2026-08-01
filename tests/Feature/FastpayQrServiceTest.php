<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Nizaamomer\LaravelFastpay\Contracts\FastpayQrServiceContract;
use Nizaamomer\LaravelFastpay\Data\QrData;
use Nizaamomer\LaravelFastpay\Data\QrStatusData;
use Nizaamomer\LaravelFastpay\Exceptions\FastpayException;
use Nizaamomer\LaravelFastpay\Models\FastpayPayment;

it('generates a QR code', function () {
    Http::fake([
        '*/api/v1/public/vending/qr' => Http::response([
            'code' => 200,
            'messages' => 'QR generation request was successful',
            'errors' => [],
            'data' => [
                'qrUrl' => 'https://xxxxxxxxxxxxxxxxxxxxxx.png',
                'qrText' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            ],
        ], 200),
    ]);

    $qr = app(FastpayQrServiceContract::class)->generate('VENDORD00123456', 1500);

    expect($qr)->toBeInstanceOf(QrData::class)
        ->and($qr->qrUrl)->toBe('https://xxxxxxxxxxxxxxxxxxxxxx.png');

    expect($qr->deepLink('VENDORD00123456', 'MyApp'))
        ->toBe('appFpp://fast-pay.cash/qrpay?qrdata=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx&clientUri=appfpclientMyApp&transactionId=VENDORD00123456');
});

it('rejects a QR amount below the 1000 minimum', function () {
    app(FastpayQrServiceContract::class)->generate('VENDORD00123456', 500);
})->throws(InvalidArgumentException::class);

it('validates a QR payment', function () {
    Http::fake([
        '*/api/v1/public/vending/validate' => Http::response([
            'code' => 200,
            'messages' => 'Payment was successful.',
            'errors' => [],
            'data' => [
                'gw_transaction_id' => 'xxxxxxxxxxxxx',
                'merchant_order_id' => 'VENDORD00123456',
                'received_amount' => 250,
                'currency' => 'IQD',
                'status' => 'success',
                'customer_name' => 'John Doe',
                'customer_mobile_number' => '+964xxxxxxxxxx',
                'at' => '2021-03-02 13:39:20',
            ],
        ], 200),
    ]);

    $validation = app(FastpayQrServiceContract::class)->validate('VENDORD00123456');

    expect($validation->isSuccessful())->toBeTrue();
});

it('refunds a QR payment', function () {
    Http::fake([
        '*/api/v1/public/payment/refund' => Http::response([
            'code' => 200,
            'messages' => 'Refund was successful.',
            'errors' => [],
            'data' => [
                'summary' => [
                    'recipient' => [
                        'name' => 'John Doe',
                        'mobile_number' => '+964xxxxxxxxxx',
                        'avatar' => 'https://xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.png',
                    ],
                    'invoice_id' => 'xxxxxxxxxx',
                ],
            ],
        ], 200),
    ]);

    $refund = app(FastpayQrServiceContract::class)->refund('VENDORD00123456', '+9641000000004', 250.0);

    expect($refund->invoiceId)->toBe('xxxxxxxxxx');
});

it('treats a QR logical failure as a request failure', function () {
    Http::fake([
        '*/api/v1/public/vending/qr' => Http::response([
            'code' => 422,
            'messages' => 'Sorry! Something went wrong.',
            'errors' => [],
            'data' => null,
        ], 200),
    ]);

    app(FastpayQrServiceContract::class)->generate('VENDORD00123456', 1500);
})->throws(FastpayException::class);

it('polls a QR payment status without throwing for an unpaid order', function () {
    Http::fake([
        '*/api/v1/public/vending/status' => Http::response([
            'code' => 200,
            'messages' => [],
            'errors' => [],
            'data' => [
                'order_id' => 'VENDORD00123456',
                'received_amount' => '0.00',
                'currency' => 'IQD',
                'payment_status' => 'UNPAID',
            ],
        ], 200),
    ]);

    $status = app(FastpayQrServiceContract::class)->status('VENDORD00123456');

    expect($status)->toBeInstanceOf(QrStatusData::class)
        ->and($status->isPaid())->toBeFalse();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/vending/validate'));
});

it('does not call validate() when confirmIfPaid is true but the order is still unpaid', function () {
    Http::fake([
        '*/api/v1/public/vending/status' => Http::response([
            'code' => 200,
            'messages' => [],
            'errors' => [],
            'data' => [
                'order_id' => 'VENDORD00123456',
                'received_amount' => '0.00',
                'currency' => 'IQD',
                'payment_status' => 'UNPAID',
            ],
        ], 200),
    ]);

    app(FastpayQrServiceContract::class)->status('VENDORD00123456', confirmIfPaid: true);

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/vending/validate'));
});

it('calls validate() and persists customer details when confirmIfPaid is true and the order is paid', function () {
    Http::fake([
        '*/api/v1/public/vending/status' => Http::response([
            'code' => 200,
            'messages' => [],
            'errors' => [],
            'data' => [
                'order_id' => 'VENDORD00123456',
                'received_amount' => '250.00',
                'currency' => 'IQD',
                'payment_status' => 'PAID',
            ],
        ], 200),
        '*/api/v1/public/vending/validate' => Http::response([
            'code' => 200,
            'messages' => [],
            'errors' => [],
            'data' => [
                'gw_transaction_id' => 'xxxxxxxxxxxxx',
                'merchant_order_id' => 'VENDORD00123456',
                'received_amount' => 250,
                'currency' => 'IQD',
                'status' => 'success',
                'customer_name' => 'John Doe',
                'customer_mobile_number' => '+9641000000004',
                'at' => '2023-12-13 15:32:00',
            ],
        ], 200),
    ]);

    $status = app(FastpayQrServiceContract::class)->status('VENDORD00123456', confirmIfPaid: true);

    expect($status->isPaid())->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/vending/validate'));

    expect(FastpayPayment::query()->where('order_id', 'VENDORD00123456')->value('customer_mobile_number'))
        ->toBe('+9641000000004');
});

it('still returns a paid status when confirmIfPaid validate() fails', function () {
    Http::fake([
        '*/api/v1/public/vending/status' => Http::response([
            'code' => 200,
            'messages' => [],
            'errors' => [],
            'data' => [
                'order_id' => 'VENDORD00123456',
                'received_amount' => '250.00',
                'currency' => 'IQD',
                'payment_status' => 'PAID',
            ],
        ], 200),
        '*/api/v1/public/vending/validate' => Http::response([
            'code' => 500,
            'messages' => ['Something went wrong.'],
            'errors' => [],
            'data' => null,
        ], 200),
    ]);

    $status = app(FastpayQrServiceContract::class)->status('VENDORD00123456', confirmIfPaid: true);

    expect($status->isPaid())->toBeTrue();
});

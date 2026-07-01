<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Nizaamomer\LaravelFastpay\Contracts\FastpayPaymentServiceContract;
use Nizaamomer\LaravelFastpay\Data\PaymentInitiationData;
use Nizaamomer\LaravelFastpay\Enums\PaymentStatus;
use Nizaamomer\LaravelFastpay\Exceptions\FastpayException;
use Nizaamomer\LaravelFastpay\Models\FastpayPayment;
use Nizaamomer\LaravelFastpay\Models\FastpayRefund;

it('initiates a payment and persists it', function () {
    Http::fake([
        '*/api/v1/public/pgw/payment/initiation' => Http::response([
            'code' => 200,
            'messages' => ['Payment Initiation request processed successfully.'],
            'data' => [
                'redirect_uri' => 'https://staging-pgw.fast-pay.iq/pay?token=fc334490-348d-4040',
            ],
        ], 200),
    ]);

    $initiation = app(FastpayPaymentServiceContract::class)->initiate('ORDER00123456', [
        ['name' => 'Scarf', 'qty' => 1, 'unit_price' => 5000],
    ]);

    expect($initiation)->toBeInstanceOf(PaymentInitiationData::class)
        ->and($initiation->redirectUri)->toContain('token=');

    expect(FastpayPayment::query()->where('order_id', 'ORDER00123456')->value('amount'))
        ->toEqual(5000);
});

it('rejects an order id outside the 8-32 alphanumeric contract', function () {
    app(FastpayPaymentServiceContract::class)->initiate('short', [
        ['name' => 'Scarf', 'qty' => 1, 'unit_price' => 5000],
    ]);
})->throws(InvalidArgumentException::class);

it('treats an HTTP-200 logical failure as a request failure', function () {
    Http::fake([
        '*/api/v1/public/pgw/payment/initiation' => Http::response([
            'code' => 422,
            'messages' => ['Sorry! The Store ID and Store Password combination is wrong.'],
            'data' => null,
        ], 200),
    ]);

    app(FastpayPaymentServiceContract::class)->initiate('ORDER00123456', [
        ['name' => 'Scarf', 'qty' => 1, 'unit_price' => 5000],
    ]);
})->throws(FastpayException::class);

it('validates a payment and persists it', function () {
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
                'transaction_id' => 'AXMNP7A004',
                'order_id' => 'ORDER00123456',
                'customer_account_no' => '+9641000000004',
                'status' => 'Success',
                'received_at' => '2023-12-13 15:32:00',
            ],
        ], 200),
    ]);

    $validation = app(FastpayPaymentServiceContract::class)->validate('ORDER00123456');

    expect($validation->isSuccessful())->toBeTrue()
        ->and($validation->status)->toBe(PaymentStatus::Success)
        ->and($validation->receivedAmount)->toBe(250.0);

    expect(FastpayPayment::query()->where('order_id', 'ORDER00123456')->value('status'))
        ->toBe(PaymentStatus::Success);
});

it('refunds a payment when a refund secret key is configured', function () {
    FastpayPayment::query()->create([
        'store' => 'default',
        'order_id' => 'ORDER00123456',
        'amount' => 250,
        'currency' => 'IQD',
        'status' => PaymentStatus::Success,
    ]);

    Http::fake([
        '*/api/v1/public/pgw/payment/refund' => Http::response([
            'code' => 200,
            'messages' => ['messages.successfully_refund'],
            'data' => [
                'summary' => [
                    'recipient' => [
                        'name' => 'John Doe',
                        'mobile_number' => '+9641000000004',
                        'avatar' => 'https://staging-asset.fast-pay.iq/avatar.jpg',
                    ],
                    'invoice_id' => 'CXMNPZQ030',
                ],
            ],
        ], 200),
    ]);

    $refund = app(FastpayPaymentServiceContract::class)->refund('ORDER00123456', '+9641000000004', 250.0);

    expect($refund->invoiceId)->toBe('CXMNPZQ030')
        ->and($refund->recipientName)->toBe('John Doe');

    expect(FastpayRefund::query()->where('invoice_id', 'CXMNPZQ030')->value('refunded'))->toBeTrue();
});

it('rejects refunding without a configured refund secret key', function () {
    config()->set('fastpay.stores.default.refund_secret_key', null);

    app(FastpayPaymentServiceContract::class)->refund('ORDER00123456', '+9641000000004', 250.0);
})->throws(FastpayException::class);

it('rejects an invalid msisdn', function () {
    app(FastpayPaymentServiceContract::class)->refund('ORDER00123456', '0770000000', 250.0);
})->throws(InvalidArgumentException::class);

it('checks refund validation status', function () {
    Http::fake([
        '*/api/v1/public/pgw/payment/refund/validation' => Http::response([
            'code' => 200,
            'messages' => [],
            'data' => [
                'order_id' => 'ORDER00123456',
                'refund_status' => true,
                'status_checked_at' => '2023-12-13 15:59:43',
            ],
        ], 200),
    ]);

    $status = app(FastpayPaymentServiceContract::class)->refundStatus('ORDER00123456');

    expect($status->refunded)->toBeTrue();
});

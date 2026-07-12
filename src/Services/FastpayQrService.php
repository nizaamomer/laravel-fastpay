<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Services;

use Nizaamomer\LaravelFastpay\Contracts\FastpayQrServiceContract;
use Nizaamomer\LaravelFastpay\Data\PaymentValidationData;
use Nizaamomer\LaravelFastpay\Data\QrData;
use Nizaamomer\LaravelFastpay\Data\RefundData;
use Nizaamomer\LaravelFastpay\Events\PaymentRefunded;
use Nizaamomer\LaravelFastpay\Events\PaymentValidated;
use Nizaamomer\LaravelFastpay\Exceptions\FastpayException;
use Nizaamomer\LaravelFastpay\Services\Concerns\TalksToFastpay;

/**
 * FastPay's QR vending API — for vending machines, kiosks, POS screens and
 * mobile apps where the customer scans (or deep-links into) a QR instead of
 * being redirected to the hosted payment page.
 *
 * Note the QR API uses camelCase request keys (storeId, storePassword)
 * unlike the payment gateway's snake_case — that inconsistency is FastPay's,
 * handled here so you never see it.
 */
final class FastpayQrService implements FastpayQrServiceContract
{
    use TalksToFastpay;

    /**
     * Generates a payment QR. FastPay requires a minimum amount of 1000 IQD
     * for QR payments. Returns both the QR image URL and the raw qrText,
     * which can also be turned into a mobile deep link via
     * QrData::deepLink().
     */
    public function generate(string $orderId, float $amount, ?string $store = null): QrData
    {
        $this->assertValidOrderId($orderId);

        if ($amount < 1000) {
            throw FastpayException::amountBelowQrMinimum($amount);
        }

        $store ??= (string) config('fastpay.default');
        $config = $this->storeConfig($store);

        $data = $this->post($store, 'qr', 'QR generation', '/api/v1/public/vending/qr', [
            'storeId' => $config['store_id'],
            'storePassword' => $config['store_password'],
            'orderId' => $orderId,
            'billAmount' => (int) round($amount),
            'currency' => (string) config('fastpay.currency', 'IQD'),
        ]);

        return QrData::fromArray($data);
    }

    /**
     * Fetches the authoritative payment result for a QR order. Same trust
     * rules as the gateway: never fulfil from the IPN body alone.
     */
    public function validate(string $orderId, ?string $store = null): PaymentValidationData
    {
        $this->assertValidOrderId($orderId);

        $store ??= (string) config('fastpay.default');
        $config = $this->storeConfig($store);

        $data = $this->post($store, 'qr', 'QR payment validation', '/api/v1/public/vending/validate', [
            'storeId' => $config['store_id'],
            'storePassword' => $config['store_password'],
            'orderId' => $orderId,
        ]);

        $validation = PaymentValidationData::fromArray($data);

        PaymentValidated::dispatch($validation, $store);

        return $validation;
    }

    /**
     * Refunds a QR payment to the customer's FastPay wallet. Unlike the
     * gateway refund, this endpoint does not use a refund secret key.
     */
    public function refund(string $orderId, string $msisdn, float $amount, ?string $store = null): RefundData
    {
        $this->assertValidOrderId($orderId);
        $this->assertValidMsisdn($msisdn);

        if ($amount <= 0) {
            throw FastpayException::invalidAmount($amount);
        }

        $store ??= (string) config('fastpay.default');
        $config = $this->storeConfig($store);

        $data = $this->post($store, 'qr', 'QR payment refund', '/api/v1/public/payment/refund', [
            'storeId' => $config['store_id'],
            'storePassword' => $config['store_password'],
            'orderId' => $orderId,
            'msisdn' => $msisdn,
            'amount' => (int) round($amount),
        ]);

        $refund = RefundData::fromArray($data);

        PaymentRefunded::dispatch($refund, $orderId, $amount, $msisdn, $store);

        return $refund;
    }
}

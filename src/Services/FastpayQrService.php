<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Services;

use Illuminate\Support\Facades\Log;
use Nizaamomer\LaravelFastpay\Contracts\FastpayQrServiceContract;
use Nizaamomer\LaravelFastpay\Data\PaymentValidationData;
use Nizaamomer\LaravelFastpay\Data\QrData;
use Nizaamomer\LaravelFastpay\Data\QrStatusData;
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
     * Polls the current status of a QR order. Unlike validate(), this always
     * returns HTTP 200 — even for unpaid/declined orders — with the outcome
     * in paymentStatus (PAID/UNPAID/DECLINED), so no try/catch is needed to
     * distinguish "not paid yet" from a genuine request failure.
     *
     * $confirmIfPaid: when true and the result is PAID, also calls validate()
     * once as a best-effort side effect. That's the only thing that (a)
     * satisfies FastPay's documented requirement to re-confirm a result
     * before trusting it, matching what you're supposed to do with an IPN,
     * and (b) fires the PaymentValidated event, which is what actually
     * persists customer_name/customer_mobile_number via
     * PersistFastpayPayment — status() alone never triggers either. A
     * failure here is logged and swallowed: status() has already succeeded,
     * so a hiccup in this enrichment step must never make an already-paid
     * order look unpaid to the caller. Defaults to false so status()'s
     * existing "cheap, side-effect-free, safe to poll" contract is
     * unchanged for anyone not opting in.
     */
    public function status(string $orderId, ?string $store = null, bool $confirmIfPaid = false): QrStatusData
    {
        $this->assertValidOrderId($orderId);

        $store ??= (string) config('fastpay.default');
        $config = $this->storeConfig($store);

        $data = $this->post($store, 'qr', 'QR payment status', '/api/v1/public/vending/status', [
            'storeId' => $config['store_id'],
            'storePassword' => $config['store_password'],
            'orderId' => $orderId,
        ]);

        $status = QrStatusData::fromArray($data);

        if ($confirmIfPaid && $status->isPaid()) {
            try {
                $this->validate($orderId, $store);
            } catch (\Throwable $e) {
                Log::warning('[FastPay] confirmIfPaid validate() failed after status()=PAID', [
                    'order_id' => $orderId,
                    'store' => $store,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $status;
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

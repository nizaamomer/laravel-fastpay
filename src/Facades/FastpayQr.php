<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Facades;

use Illuminate\Support\Facades\Facade;
use Nizaamomer\LaravelFastpay\Contracts\FastpayQrServiceContract;
use Nizaamomer\LaravelFastpay\Data\PaymentValidationData;
use Nizaamomer\LaravelFastpay\Data\QrData;
use Nizaamomer\LaravelFastpay\Data\QrStatusData;
use Nizaamomer\LaravelFastpay\Data\RefundData;

/**
 * @method static QrData generate(string $orderId, float $amount, ?string $store = null)
 * @method static PaymentValidationData validate(string $orderId, ?string $store = null)
 * @method static QrStatusData status(string $orderId, ?string $store = null)
 * @method static RefundData refund(string $orderId, string $msisdn, float $amount, ?string $store = null)
 *
 * @see FastpayQrServiceContract
 */
class FastpayQr extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FastpayQrServiceContract::class;
    }
}

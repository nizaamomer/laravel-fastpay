<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Contracts;

use Nizaamomer\LaravelFastpay\Data\PaymentValidationData;
use Nizaamomer\LaravelFastpay\Data\QrData;
use Nizaamomer\LaravelFastpay\Data\RefundData;

interface FastpayQrServiceContract
{
    public function generate(string $orderId, float $amount, ?string $store = null): QrData;

    public function validate(string $orderId, ?string $store = null): PaymentValidationData;

    public function refund(string $orderId, string $msisdn, float $amount, ?string $store = null): RefundData;
}

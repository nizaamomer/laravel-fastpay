<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Contracts;

use Nizaamomer\LaravelFastpay\Data\CartItem;
use Nizaamomer\LaravelFastpay\Data\PaymentInitiationData;
use Nizaamomer\LaravelFastpay\Data\PaymentValidationData;
use Nizaamomer\LaravelFastpay\Data\RefundData;
use Nizaamomer\LaravelFastpay\Data\RefundValidationData;

interface FastpayPaymentServiceContract
{
    /**
     * @param  array<int, CartItem|array<string, mixed>>  $cart
     */
    public function initiate(
        string $orderId,
        array $cart,
        ?float $amount = null,
        ?string $successUrl = null,
        ?string $cancelUrl = null,
        ?string $callbackUrl = null,
        ?string $store = null,
    ): PaymentInitiationData;

    public function validate(string $orderId, ?string $store = null): PaymentValidationData;

    public function refund(string $orderId, string $msisdn, float $amount, ?string $store = null): RefundData;

    public function refundStatus(string $orderId, ?string $store = null): RefundValidationData;
}

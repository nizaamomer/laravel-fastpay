<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Facades;

use Illuminate\Support\Facades\Facade;
use Nizaamomer\LaravelFastpay\Contracts\FastpayPaymentServiceContract;
use Nizaamomer\LaravelFastpay\Data\CartItem;
use Nizaamomer\LaravelFastpay\Data\PaymentInitiationData;
use Nizaamomer\LaravelFastpay\Data\PaymentValidationData;
use Nizaamomer\LaravelFastpay\Data\RefundData;
use Nizaamomer\LaravelFastpay\Data\RefundValidationData;

/**
 * @method static PaymentInitiationData initiate(string $orderId, array<int, CartItem|array<string, mixed>> $cart, ?float $amount = null, ?string $successUrl = null, ?string $cancelUrl = null, ?string $callbackUrl = null, ?string $store = null)
 * @method static PaymentValidationData validate(string $orderId, ?string $store = null)
 * @method static RefundData refund(string $orderId, string $msisdn, float $amount, ?string $store = null)
 * @method static RefundValidationData refundStatus(string $orderId, ?string $store = null)
 *
 * @see FastpayPaymentServiceContract
 */
class FastpayPayment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FastpayPaymentServiceContract::class;
    }
}

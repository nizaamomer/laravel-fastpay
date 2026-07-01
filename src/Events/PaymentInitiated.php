<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Nizaamomer\LaravelFastpay\Data\PaymentInitiationData;

final class PaymentInitiated
{
    use Dispatchable;

    public function __construct(
        public readonly PaymentInitiationData $initiation,
        public readonly string $orderId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $store,
    ) {}
}

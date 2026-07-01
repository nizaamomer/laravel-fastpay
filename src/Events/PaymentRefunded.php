<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Nizaamomer\LaravelFastpay\Data\RefundData;

final class PaymentRefunded
{
    use Dispatchable;

    public function __construct(
        public readonly RefundData $refund,
        public readonly string $orderId,
        public readonly float $amount,
        public readonly string $msisdn,
        public readonly string $store,
    ) {}
}

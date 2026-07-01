<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Nizaamomer\LaravelFastpay\Data\PaymentValidationData;

final class PaymentValidated
{
    use Dispatchable;

    public function __construct(
        public readonly PaymentValidationData $validation,
        public readonly string $store,
    ) {}
}

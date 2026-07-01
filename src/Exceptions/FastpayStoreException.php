<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Exceptions;

use RuntimeException;

class FastpayStoreException extends RuntimeException
{
    public static function unknownStore(string $store): self
    {
        return new self("FastPay store [{$store}] is not configured in config/fastpay.php.");
    }

    public static function unknownEnvironment(string $store, string $environment): self
    {
        return new self(
            "FastPay store [{$store}] has environment [{$environment}] but only 'staging' and 'production' are supported."
        );
    }
}

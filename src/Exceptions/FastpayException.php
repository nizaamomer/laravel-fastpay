<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Exceptions;

use InvalidArgumentException;
use RuntimeException;

class FastpayException extends RuntimeException
{
    /**
     * @param  array<int, string>  $messages
     */
    public static function requestFailed(string $action, int $code, array $messages): self
    {
        $detail = $messages === [] ? 'no error message returned' : implode(' ', $messages);

        return new self("FastPay {$action} request failed with code {$code}: {$detail}");
    }

    public static function invalidAmount(float $amount): InvalidArgumentException
    {
        return new InvalidArgumentException("Amount must be greater than zero, got {$amount}.");
    }

    public static function amountBelowQrMinimum(float $amount): InvalidArgumentException
    {
        return new InvalidArgumentException("QR payments require a minimum amount of 1000, got {$amount}.");
    }

    public static function invalidOrderId(string $orderId): InvalidArgumentException
    {
        return new InvalidArgumentException(
            "Order ID [{$orderId}] must be 8-32 alphanumeric characters per FastPay's API contract."
        );
    }

    public static function invalidMsisdn(string $msisdn): InvalidArgumentException
    {
        return new InvalidArgumentException("MSISDN [{$msisdn}] does not look like a valid +964 phone number.");
    }

    public static function missingRefundSecretKey(string $store): self
    {
        return new self(
            "FastPay store [{$store}] has no refund_secret_key configured — set FASTPAY_REFUND_SECRET_KEY "
            .'(found under Store Details in the Merchant Panel) to process gateway refunds.'
        );
    }
}

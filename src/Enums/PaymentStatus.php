<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Enums;

enum PaymentStatus: string
{
    /**
     * Local-only state for a payment that has been initiated but not yet
     * confirmed by FastPay's validation API. FastPay itself never returns
     * this value.
     */
    case Pending = 'Pending';

    case Success = 'Success';
    case Failed = 'Failed';
    case Cancel = 'Cancel';

    /**
     * FastPay is inconsistent about casing across its APIs ("Success" from
     * the payment gateway, "success" from the QR API) — normalize here.
     */
    public static function fromApi(string $value): self
    {
        return self::from(ucfirst(strtolower($value)));
    }
}

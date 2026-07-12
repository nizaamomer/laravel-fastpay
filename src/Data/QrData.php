<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Data;

use Nizaamomer\LaravelFastpay\Exceptions\FastpayException;
use Nizaamomer\LaravelFastpay\Support\DeepLink;

final readonly class QrData
{
    public function __construct(
        public string $qrUrl,
        public string $qrText,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            qrUrl: $data['qrUrl'],
            qrText: $data['qrText'],
        );
    }

    /**
     * Builds the appFpp:// deep link a mobile app can open to pay this QR
     * inside the FastPay app. See DeepLink for details.
     *
     * $clientUri defaults to config('fastpay.client_uri') (FASTPAY_CLIENT_URI)
     * — pass it explicitly only if this call needs a different scheme than
     * your app's configured default (e.g. a multi-brand app).
     */
    public function deepLink(string $orderId, ?string $clientUri = null): string
    {
        $clientUri ??= (string) config('fastpay.client_uri');

        if ($clientUri === '') {
            throw FastpayException::missingClientUri();
        }

        return DeepLink::qrPay($this->qrText, $clientUri, $orderId);
    }
}

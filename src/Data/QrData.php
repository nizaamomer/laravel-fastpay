<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Data;

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
     */
    public function deepLink(string $clientUri, string $orderId): string
    {
        return DeepLink::qrPay($this->qrText, $clientUri, $orderId);
    }
}

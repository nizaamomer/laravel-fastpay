<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Data;

final readonly class PaymentInitiationData
{
    public function __construct(
        public string $redirectUri,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            redirectUri: $data['redirect_uri'],
        );
    }
}

<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Data;

use Carbon\CarbonImmutable;

final readonly class RefundValidationData
{
    public function __construct(
        public string $orderId,
        public bool $refunded,
        public ?CarbonImmutable $statusCheckedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderId: $data['order_id'],
            refunded: (bool) $data['refund_status'],
            statusCheckedAt: isset($data['status_checked_at']) ? CarbonImmutable::parse($data['status_checked_at']) : null,
        );
    }
}

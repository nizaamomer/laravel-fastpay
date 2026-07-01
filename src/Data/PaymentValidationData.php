<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Data;

use Carbon\CarbonImmutable;
use Nizaamomer\LaravelFastpay\Enums\PaymentStatus;

/**
 * The authoritative payment record returned by FastPay's validation API.
 * Both the payment gateway and QR APIs return this same shape.
 */
final readonly class PaymentValidationData
{
    public function __construct(
        public string $gwTransactionId,
        public string $merchantOrderId,
        public float $receivedAmount,
        public string $currency,
        public PaymentStatus $status,
        public ?string $customerName,
        public ?string $customerMobileNumber,
        public ?string $customerAccountNo,
        public ?CarbonImmutable $at,
        public ?CarbonImmutable $receivedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            gwTransactionId: $data['gw_transaction_id'],
            merchantOrderId: $data['merchant_order_id'],
            receivedAmount: (float) $data['received_amount'],
            currency: $data['currency'],
            status: PaymentStatus::fromApi($data['status']),
            customerName: $data['customer_name'] ?? null,
            customerMobileNumber: $data['customer_mobile_number'] ?? null,
            customerAccountNo: $data['customer_account_no'] ?? null,
            at: isset($data['at']) ? CarbonImmutable::parse($data['at']) : null,
            receivedAt: isset($data['received_at']) ? CarbonImmutable::parse($data['received_at']) : null,
        );
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Success;
    }
}

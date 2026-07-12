<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Data;

use Carbon\CarbonImmutable;

/**
 * The response shape of the QR vending "Get Payment Status" API. Distinct
 * from PaymentValidationData: this endpoint uses "payment_status" (not
 * "status"), always returns HTTP 200 — even for unpaid/declined orders —
 * and fills unknown fields with the literal string "N/A" instead of null.
 */
final readonly class QrStatusData
{
    public function __construct(
        public string $orderId,
        public ?string $gwTransactionId,
        public ?string $transactionId,
        public float $receivedAmount,
        public string $currency,
        public string $paymentStatus,
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
        $clean = fn (?string $value): ?string => ($value === null || $value === '' || $value === 'N/A') ? null : $value;

        return new self(
            orderId: $data['order_id'] ?? $data['merchant_order_id'],
            gwTransactionId: $clean($data['gw_transaction_id'] ?? null),
            transactionId: $clean($data['transaction_id'] ?? null),
            receivedAmount: (float) $data['received_amount'],
            currency: $data['currency'],
            paymentStatus: strtoupper((string) $data['payment_status']),
            customerName: $clean($data['customer_name'] ?? null),
            customerMobileNumber: $clean($data['customer_mobile_number'] ?? null),
            customerAccountNo: $clean($data['customer_account_no'] ?? null),
            at: ($at = $clean($data['at'] ?? null)) !== null ? CarbonImmutable::parse($at) : null,
            receivedAt: ($receivedAt = $clean($data['received_at'] ?? null)) !== null ? CarbonImmutable::parse($receivedAt) : null,
        );
    }

    public function isPaid(): bool
    {
        return $this->paymentStatus === 'PAID';
    }
}

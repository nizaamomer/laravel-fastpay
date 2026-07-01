<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Data;

final readonly class RefundData
{
    public function __construct(
        public string $invoiceId,
        public ?string $recipientName,
        public ?string $recipientMobileNumber,
        public ?string $recipientAvatar,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $summary = $data['summary'] ?? [];
        $recipient = $summary['recipient'] ?? [];

        return new self(
            // The gateway API calls this "invoice_id"; older QR docs show
            // "refund_invoice_id" — accept both.
            invoiceId: $summary['invoice_id'] ?? $summary['refund_invoice_id'],
            recipientName: $recipient['name'] ?? null,
            recipientMobileNumber: $recipient['mobile_number'] ?? null,
            recipientAvatar: $recipient['avatar'] ?? null,
        );
    }
}

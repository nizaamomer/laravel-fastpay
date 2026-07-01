<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $payment_id
 * @property string|null $invoice_id
 * @property string $msisdn
 * @property float $amount
 * @property bool $refunded
 * @property CarbonImmutable|null $status_checked_at
 */
class FastpayRefund extends Model
{
    protected $table = 'fastpay_refunds';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refunded' => 'boolean',
            'status_checked_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<FastpayPayment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(FastpayPayment::class, 'payment_id');
    }
}

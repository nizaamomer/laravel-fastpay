<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Nizaamomer\LaravelFastpay\Enums\PaymentStatus;

/**
 * @property string $store
 * @property string $order_id
 * @property string|null $gw_transaction_id
 * @property float $amount
 * @property string $currency
 * @property PaymentStatus $status
 * @property string|null $customer_name
 * @property string|null $customer_mobile_number
 * @property string|null $redirect_uri
 * @property CarbonImmutable|null $validated_at
 * @property array<string, mixed>|null $meta
 */
class FastpayPayment extends Model
{
    protected $table = 'fastpay_payments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'validated_at' => 'immutable_datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasOne<FastpayRefund, $this>
     */
    public function refund(): HasOne
    {
        return $this->hasOne(FastpayRefund::class, 'payment_id');
    }
}

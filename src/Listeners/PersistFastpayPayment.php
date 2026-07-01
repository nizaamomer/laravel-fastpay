<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Listeners;

use Nizaamomer\LaravelFastpay\Events\PaymentInitiated;
use Nizaamomer\LaravelFastpay\Events\PaymentRefunded;
use Nizaamomer\LaravelFastpay\Events\PaymentValidated;
use Nizaamomer\LaravelFastpay\Models\FastpayPayment;
use Nizaamomer\LaravelFastpay\Models\FastpayRefund;

final class PersistFastpayPayment
{
    public function onInitiated(PaymentInitiated $event): void
    {
        FastpayPayment::query()->updateOrCreate(
            ['order_id' => $event->orderId],
            [
                'store' => $event->store,
                'amount' => $event->amount,
                'currency' => $event->currency,
                'redirect_uri' => $event->initiation->redirectUri,
            ],
        );
    }

    public function onValidated(PaymentValidated $event): void
    {
        $validation = $event->validation;

        // A response missing a field here would be a genuine data-loss risk
        // (see the same class of bug fixed in laravel-fib's status sync) —
        // only write fields FastPay actually returned.
        FastpayPayment::query()->updateOrCreate(
            ['order_id' => $validation->merchantOrderId],
            array_filter([
                'store' => $event->store,
                'gw_transaction_id' => $validation->gwTransactionId,
                'amount' => $validation->receivedAmount,
                'currency' => $validation->currency,
                'status' => $validation->status,
                'customer_name' => $validation->customerName,
                'customer_mobile_number' => $validation->customerMobileNumber,
                'validated_at' => $validation->at,
            ], fn ($value) => $value !== null),
        );
    }

    public function onRefunded(PaymentRefunded $event): void
    {
        $payment = FastpayPayment::query()->where('order_id', $event->orderId)->first();

        if ($payment === null) {
            return;
        }

        FastpayRefund::query()->updateOrCreate(
            ['payment_id' => $payment->id],
            [
                'invoice_id' => $event->refund->invoiceId,
                'msisdn' => $event->msisdn,
                'amount' => $event->amount,
                'refunded' => true,
            ],
        );
    }
}

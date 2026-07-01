<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Console\Commands;

use Illuminate\Console\Command;
use Nizaamomer\LaravelFastpay\Contracts\FastpayPaymentServiceContract;
use Nizaamomer\LaravelFastpay\Enums\PaymentStatus;
use Nizaamomer\LaravelFastpay\Models\FastpayPayment;
use Throwable;

/**
 * Re-checks payments still Pending directly against FastPay.
 *
 * FastPay's IPN only fires for successful payments — a failed, cancelled,
 * or abandoned checkout never notifies you at all, so this is the only way
 * to learn a pending order's final outcome without the customer manually
 * completing the success/cancel redirect.
 */
class SyncFastpayStatuses extends Command
{
    protected $signature = 'fastpay:sync-statuses {--limit=100}';

    protected $description = 'Re-check pending FastPay payment statuses directly from the FastPay API';

    public function handle(FastpayPaymentServiceContract $payments): int
    {
        $limit = (int) $this->option('limit');

        $pending = FastpayPayment::query()
            ->where('status', PaymentStatus::Pending)
            ->limit($limit)
            ->get();

        foreach ($pending as $payment) {
            try {
                $payments->validate($payment->order_id, $payment->store);
            } catch (Throwable $e) {
                // FastPay returns a 404-style error for orders that are
                // genuinely still unpaid, not just for real failures —
                // that's expected here, so just note it and move on.
                $this->warn("Order {$payment->order_id} not yet resolved: {$e->getMessage()}");
            }
        }

        $this->info("Checked {$pending->count()} pending payment(s).");

        return self::SUCCESS;
    }
}

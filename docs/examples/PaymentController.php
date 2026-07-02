<?php

declare(strict_types=1);

/**
 * Full reference example for nizaamomer/laravel-fastpay.
 *
 * This is illustrative, not part of the package's autoload — copy what you
 * need into your own app. It assumes an App\Models\Order with a `total`
 * column.
 *
 * Covers every public method across all three parts: Part 1 — Payment
 * Gateway (initiate, IPN webhook, validate, refund, refund status),
 * Part 2 — QR Vending (generate, validate, refund), and Part 3 — Mobile
 * Deep Links (turning a QR into a deep link for native apps).
 */

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Nizaamomer\LaravelFastpay\Facades\FastpayPayment;
use Nizaamomer\LaravelFastpay\Facades\FastpayQr;
use Nizaamomer\LaravelFastpay\Models\FastpayPayment as FastpayPaymentModel;

class PaymentController extends Controller
{
    /**
     * Start a FastPay gateway payment for an order and redirect the
     * customer to the hosted payment page.
     */
    public function pay(Order $order)
    {
        $initiation = FastpayPayment::initiate(
            orderId: (string) $order->id, // must be 8-32 alphanumeric characters
            cart: $order->items->map(fn ($item) => [
                'name' => $item->name,
                'qty' => $item->qty,
                'unit_price' => $item->unit_price,
            ])->all(),
            // successUrl, cancelUrl and callbackUrl are optional — they fall
            // back to FASTPAY_SUCCESS_URL / FASTPAY_CANCEL_URL /
            // FASTPAY_CALLBACK_URL from your .env when omitted.
        );

        return redirect($initiation->redirectUri);
    }

    /**
     * FastPay's IPN webhook — fired only for successful payments. Register
     * this URL in the FastPay Merchant Panel's Store Configuration, not in
     * your own routes/web.php as a "callback" the customer visits.
     *
     * Never trust the POST body directly: it isn't signed, so anyone who
     * learns this URL could fake a "Success" notification. Always re-verify
     * with FastpayPayment::validate() — this is FastPay's own mandated step.
     */
    public function ipn(Request $request)
    {
        $orderId = $request->input('merchant_order_id');

        $validation = FastpayPayment::validate($orderId);

        if ($validation->isSuccessful()) {
            FastpayPaymentModel::where('order_id', $orderId)
                ->first()
                ?->payable()->associate(Order::find($orderId))->save();

            Order::where('id', $orderId)->update(['status' => 'paid']);
        }

        return response()->noContent();
    }

    /**
     * The page the customer lands on after being redirected back from
     * FastPay (success_url/cancel_url). Re-validate here too — the IPN
     * might not have arrived yet, or the customer might land here without
     * an IPN ever firing at all (failed/cancelled payments send no IPN).
     */
    public function returned(Request $request, Order $order)
    {
        $validation = FastpayPayment::validate((string) $order->id);

        if ($validation->isSuccessful()) {
            $order->update(['status' => 'paid']);

            return redirect()->route('orders.show', $order)->with('status', 'Payment successful.');
        }

        return redirect()->route('orders.show', $order)->with('status', 'Payment was not completed.');
    }

    /**
     * Refund a paid order back to the customer's FastPay wallet. Requires
     * FASTPAY_REFUND_SECRET_KEY (found under Store Details in the Merchant
     * Panel). This moves real money — put your own approval workflow here.
     */
    public function refund(Order $order)
    {
        $refund = FastpayPayment::refund(
            orderId: (string) $order->id,
            msisdn: $order->customer_mobile_number, // +964xxxxxxxxxx
            amount: $order->total,
        );

        $order->update(['status' => 'refund_requested']);

        return response()->json(['invoice_id' => $refund->invoiceId]);
    }

    /**
     * Check whether a refund actually completed. FastPay refunds don't
     * notify you either — poll this, or rely on your own manual process.
     */
    public function refundStatus(Order $order)
    {
        $status = FastpayPayment::refundStatus((string) $order->id);

        return response()->json(['refunded' => $status->refunded]);
    }

    /**
     * QR vending flow — for kiosks, POS screens, or a mobile app where the
     * customer scans (or deep-links into) a QR instead of visiting a hosted
     * payment page. Minimum amount is 1000 IQD.
     */
    public function payByQr(Order $order)
    {
        $qr = FastpayQr::generate((string) $order->id, $order->total);

        return response()->json([
            'qr_url' => $qr->qrUrl,           // image URL to display for scanning
            'qr_text' => $qr->qrText,         // raw token, also usable for a deep link
            'deep_link' => $qr->deepLink('MyApp', (string) $order->id), // for a native app
        ]);
    }

    public function validateQr(Order $order)
    {
        $validation = FastpayQr::validate((string) $order->id);

        if ($validation->isSuccessful()) {
            $order->update(['status' => 'paid']);
        }

        return response()->json(['status' => $validation->status->value]);
    }

    public function refundQr(Order $order)
    {
        // The QR refund endpoint doesn't require a refund secret key, unlike
        // the gateway refund above.
        $refund = FastpayQr::refund(
            orderId: (string) $order->id,
            msisdn: $order->customer_mobile_number,
            amount: $order->total,
        );

        return response()->json(['invoice_id' => $refund->invoiceId]);
    }
}

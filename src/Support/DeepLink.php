<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Support;

/**
 * Builds FastPay mobile deep links.
 *
 * If the FastPay app is installed the link opens the payment screen
 * directly; if not, it redirects to the app store. After payment, FastPay
 * redirects back to the merchant app via the appfpclient{clientUri} callback
 * scheme with transactionStatus/transactionId/amount query parameters —
 * register that scheme in your Android manifest / iOS Info.plist (see the
 * README's deep link section).
 */
final class DeepLink
{
    /**
     * Deep link for paying a generated QR inside the FastPay app.
     *
     * @param  string  $qrText  the qrText/qrToken returned by the QR generation API
     * @param  string  $clientUri  your app's callback scheme suffix, e.g. "MyApp"
     *                             (FastPay will call back to appfpclientMyApp://...)
     * @param  string  $orderId  your merchant order id, echoed back as transactionId
     */
    public static function qrPay(string $qrText, string $clientUri, string $orderId): string
    {
        return 'appFpp://fast-pay.cash/qrpay?'.http_build_query([
            'qrdata' => $qrText,
            'clientUri' => 'appfpclient'.$clientUri,
            'transactionId' => $orderId,
        ]);
    }
}

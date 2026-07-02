# Laravel FastPay SDK — Payment Gateway, QR Vending & Mobile Deep Links

<p>
<a href="https://packagist.org/packages/nizaamomer/laravel-fastpay"><img src="https://img.shields.io/packagist/v/nizaamomer/laravel-fastpay.svg?style=flat-square&label=Packagist&color=orange" alt="Latest Version on Packagist"></a>
<a href="https://github.com/nizaamomer/laravel-fastpay/actions"><img src="https://img.shields.io/github/actions/workflow/status/nizaamomer/laravel-fastpay/run-tests.yml?branch=main&label=Tests&style=flat-square" alt="Tests"></a>
<a href="https://packagist.org/packages/nizaamomer/laravel-fastpay"><img src="https://img.shields.io/packagist/dt/nizaamomer/laravel-fastpay.svg?style=flat-square&label=Downloads&color=blue" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/nizaamomer/laravel-fastpay"><img src="https://img.shields.io/packagist/php-v/nizaamomer/laravel-fastpay.svg?style=flat-square&label=PHP&color=777bb4" alt="PHP Version"></a>
<a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-ff2d20?style=flat-square" alt="Laravel Version"></a>
<a href="LICENSE.md"><img src="https://img.shields.io/packagist/l/nizaamomer/laravel-fastpay.svg?style=flat-square&color=success" alt="License"></a>
</p>

A modern Laravel SDK for [FastPay Iraq](https://www.fast-pay.iq) — one package covering all three ways FastPay lets you accept money: **1) a redirect-based Payment Gateway for the web**, **2) QR Vending for kiosks/POS**, and **3) Mobile Deep Links for native Android/iOS/Flutter apps** — plus refunds, typed DTOs, multi-store support, and automatic status persistence.

Built by [Nizaam Omer](https://nizaamomer.com) — [nizaamomer.com](https://nizaamomer.com)

## Table of Contents

- [Why one package?](#why-one-package)
- [Requirements](#requirements)
- [Installation](#installation)
- [Part 1 — Payment Gateway (Web)](#part-1--payment-gateway-web)
  - [Initiating a payment](#initiating-a-payment)
  - [The IPN webhook — never trust the payload](#the-ipn-webhook--never-trust-the-payload)
  - [Validating a payment](#validating-a-payment)
  - [Refunding a payment](#refunding-a-payment)
  - [Checking refund status](#checking-refund-status)
  - [Missed notifications: the sync command](#missed-notifications-the-sync-command)
- [Part 2 — QR Vending](#part-2--qr-vending)
  - [Generating a QR](#generating-a-qr)
  - [Validating & refunding a QR payment](#validating--refunding-a-qr-payment)
- [Part 3 — Mobile Deep Links](#part-3--mobile-deep-links)
  - [Building the deep link](#building-the-deep-link)
  - [Handling the callback in your app](#handling-the-callback-in-your-app)
- [Automatic persistence](#automatic-persistence)
- [Full example](#full-example)
- [Security](#security)
- [Testing](#testing)
- [FastPay API Reference](#fastpay-api-reference)
- [Changelog](#changelog)
- [Author](#author)
- [License](#license)

## Why one package?

All three parts share the same store credentials, the same response envelope quirks, and the same validation/refund shape — splitting them into separate packages would mean re-solving the same HTTP plumbing three times over. `FastpayPayment` covers **Part 1 (Payment Gateway)** and doubles as the validate/refund API for **Part 2 (QR Vending)** via `FastpayQr`; **Part 3 (Mobile Deep Links)** is a thin, dependency-free helper that turns a Part 2 QR into a URL your native app can open. All three sit on the same underlying HTTP client and store configuration.

## Requirements

- PHP 8.2+
- Laravel 11.x, 12.x or 13.x

## Installation

```bash
composer require nizaamomer/laravel-fastpay
```

Publish the config file:

```bash
php artisan vendor:publish --tag="fastpay-config"
```

Run the migrations — creates `fastpay_payments` and `fastpay_refunds`; every payment and refund is persisted automatically, no manual tracking code needed:

```bash
php artisan migrate
```

Add your FastPay credentials to `.env` (from the Merchant Panel at [merchant.fast-pay.iq](https://merchant.fast-pay.iq)):

```env
FASTPAY_ENVIRONMENT=staging                    # "staging" or "production"
FASTPAY_STORE_ID=your-store-id                 # from Store Configuration in the Merchant Panel
FASTPAY_STORE_PASSWORD=your-store-password     # from Store Configuration — keep this out of version control
FASTPAY_REFUND_SECRET_KEY=your-refund-key      # optional, only needed for gateway refunds — found under Store Details
FASTPAY_CURRENCY=IQD                           # FastPay currently only supports IQD
FASTPAY_SUCCESS_URL=https://your-app.test/checkout/success   # Part 1 only: where FastPay redirects on success
FASTPAY_CANCEL_URL=https://your-app.test/checkout/cancel     # Part 1 only: where FastPay redirects on cancel
FASTPAY_CALLBACK_URL=https://your-app.test/fastpay/ipn       # Part 1 only: where FastPay POSTs the IPN
```

`success_url`/`cancel_url`/`callback_url` only apply to Part 1 (the web redirect flow). Parts 2 and 3 have no equivalent — the FastPay app talks to your own app's deep-link callback scheme directly instead (see [Part 3](#part-3--mobile-deep-links)).

<!--
### Multiple stores

Add more entries under `stores` in `config/fastpay.php` to accept payments through multiple FastPay merchant stores, then pass the store name as the last argument of any SDK call:

```php
FastpayPayment::initiate($orderId, $cart, store: 'second_store');
```

-->

## Part 1 — Payment Gateway (Web)

For a normal web checkout: you redirect the customer to a FastPay-hosted payment page, and they land back on your `success_url` or `cancel_url` afterward.

> **Note:** FastPay has no "cancel payment" API — `cancel_url` is purely where the browser is redirected if the customer backs out; there's nothing to call on your side for that outcome. The only merchant-initiated actions are initiate, validate, and refund.

### Initiating a payment

```php
use Nizaamomer\LaravelFastpay\Facades\FastpayPayment;

$initiation = FastpayPayment::initiate(
    orderId: (string) $order->id, // 8-32 alphanumeric characters, required by FastPay
    cart: [
        ['name' => 'Scarf', 'qty' => 1, 'unit_price' => 5000],
        ['name' => 'T-Shirt', 'qty' => 2, 'unit_price' => 10000],
    ],
    // amount, successUrl, cancelUrl and callbackUrl are all optional — amount
    // defaults to the sum of the cart, the URLs fall back to FASTPAY_SUCCESS_URL
    // / FASTPAY_CANCEL_URL / FASTPAY_CALLBACK_URL automatically when omitted.
);

return redirect($initiation->redirectUri);
```

### The IPN webhook — never trust the payload

FastPay POSTs an Instant Payment Notification to your `FASTPAY_CALLBACK_URL` **only on successful payments** — failed and cancelled payments send no notification at all. The IPN body is **not signed**, so always re-verify with `validate()` before trusting it, exactly as FastPay's own docs mandate:

```php
Route::post('/fastpay/ipn', function (\Illuminate\Http\Request $request) {
    $validation = FastpayPayment::validate($request->input('merchant_order_id'));

    if ($validation->isSuccessful()) {
        // fulfil the order
    }

    return response()->noContent();
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
  ->middleware('throttle:60,1');
```

### Validating a payment

Also call this on the page the customer lands on after being redirected back (`success_url`/`cancel_url`) — the IPN might not have arrived yet, or might never arrive at all for a failed/cancelled payment:

```php
$validation = FastpayPayment::validate($orderId);

$validation->status;           // PaymentStatus::Success | Failed | Cancel
$validation->isSuccessful();   // bool
$validation->receivedAmount;   // float
$validation->customerName;
$validation->customerMobileNumber;
```

### Refunding a payment

Requires `FASTPAY_REFUND_SECRET_KEY` (found under Store Details in the Merchant Panel).

```php
$refund = FastpayPayment::refund(
    orderId: $orderId,
    msisdn: '+9641000000004', // recipient's FastPay mobile number
    amount: 250.00,
);

$refund->invoiceId;
$refund->recipientName;
```

### Checking refund status

```php
$status = FastpayPayment::refundStatus($orderId);

$status->refunded; // bool
```

### Missed notifications: the sync command

Since only successful payments trigger an IPN, any payment left `Pending` in your database needs to be re-checked manually. Run:

```bash
php artisan fastpay:sync-statuses
```

to re-validate every pending payment directly against FastPay. Schedule it in `bootstrap/app.php`:

```php
->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
    $schedule->command('fastpay:sync-statuses')->everyFiveMinutes();
})
```

## Part 2 — QR Vending

For vending machines, kiosks, and POS screens: instead of redirecting to a web page, you generate a QR the customer scans with the FastPay app. Minimum amount is 1000 IQD.

### Generating a QR

```php
use Nizaamomer\LaravelFastpay\Facades\FastpayQr;

$qr = FastpayQr::generate($orderId, 1500.00);

$qr->qrUrl;   // image URL — display this for the customer to scan
$qr->qrText;  // raw token — hand this to Part 3 if you also want an in-app deep link
```

### Validating & refunding a QR payment

Same shape as Part 1, just called on `FastpayQr` instead:

```php
FastpayQr::validate($orderId);                              // same DTO as the gateway's validate()
FastpayQr::refund($orderId, '+9641000000004', 1500.00);      // no refund secret key needed here, unlike the gateway
```

## Part 3 — Mobile Deep Links

For a native Android/iOS/Flutter app: skip the "scan a QR with your camera" step and open the FastPay app directly from within your own app. This builds on a Part 2 QR — generate one first, then turn it into a deep link.

### Building the deep link

```php
$qr = FastpayQr::generate($orderId, 1500.00);

$deepLink = $qr->deepLink(clientUri: 'MyApp', orderId: $orderId);
// appFpp://fast-pay.cash/qrpay?qrdata=...&clientUri=appfpclientMyApp&transactionId=...
```

Opening that URL launches the FastPay app directly to the payment screen if installed, or falls back to the app store if not (that fallback logic lives in your native app, not this package).

### Handling the callback in your app

After payment, FastPay redirects back to **your** app via the `appfpclientMyApp://` scheme you register in your Android manifest / iOS `Info.plist` / Flutter `app_links` setup, with `transactionStatus`, `transactionId`, and `amount` as query parameters. This is a **native, client-side callback** — it never touches your Laravel backend, so nothing on the server side needs to handle it directly. Once your app receives it, call `FastpayQr::validate($orderId)` from your backend (same as Part 2) to confirm the outcome server-side before marking anything as paid.

The native-side registration and callback handling (Kotlin/Swift/Dart) is FastPay's own platform-specific integration, documented at [developer.fast-pay.iq](https://developer.fast-pay.iq) — this package only builds the correctly-formatted deep link URL from your backend.

## Automatic persistence

Every `initiate()`, `validate()`, and `refund()` call — from any of the three parts — fires an event (`PaymentInitiated`, `PaymentValidated`, `PaymentRefunded`) that this package listens to and upserts into `fastpay_payments`/`fastpay_refunds` automatically — no manual tracking code required. Link your own models via the `payable` polymorphic relation:

```php
use Nizaamomer\LaravelFastpay\Models\FastpayPayment;

FastpayPayment::where('order_id', $orderId)->first()?->payable()->associate($order)->save();
```

## Full example

[`docs/examples/PaymentController.php`](docs/examples/PaymentController.php) is a complete, heavily-commented controller covering every public method from all three parts — initiating a web payment, the IPN webhook, the success/cancel return page, refunding, refund status, QR generation/validation, and the deep-link flow. It's illustrative (not autoloaded), so copy what you need into your own app.

## Security

- **TLS verification is never disabled.** This SDK does not expose a way to set Guzzle's `verify => false`.
- **IPN payloads are not trusted.** `FastpayPayment::validate()` always calls FastPay directly; use it inside your IPN handler instead of reading `status` off the request body.
- **FastPay's HTTP-200-with-error-code quirk is handled for you.** FastPay returns HTTP 200 for both successes and logical failures (the real outcome is in the JSON `code` field) — this SDK treats `code !== 200` as a failure and throws, so you don't need to remember to check it yourself.
- **Order IDs and MSISDNs are format-validated** (8-32 alphanumeric; `+964` phone format) before any request is sent.
- **Amounts must be greater than zero**, and QR payments enforce FastPay's 1000 IQD minimum, before any request is sent.
- **Credentials live in `.env`,** never in version control. Rotate `FASTPAY_STORE_PASSWORD` immediately if it's ever exposed.

If you discover a security issue, please email [nizaamomer@gmail.com](mailto:nizaamomer@gmail.com) instead of using the public issue tracker.

## Testing

```bash
composer test        # Pest
composer analyse      # Larastan / PHPStan (level 8)
composer format        # Laravel Pint
```

## FastPay API Reference

See the [FastPay Developer Documentation](https://developer.fast-pay.iq) for the underlying REST API this SDK wraps.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for what's changed in each release.

## Author

**Nizaam Omer** — [nizaamomer.com](https://nizaamomer.com) · [nizaamomer@gmail.com](mailto:nizaamomer@gmail.com)

## License

MIT. See [LICENSE.md](LICENSE.md).

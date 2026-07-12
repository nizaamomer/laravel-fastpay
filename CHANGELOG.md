# Changelog

All notable changes to `nizaamomer/laravel-fastpay` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `FastpayQr::status()` + `QrStatusData` DTO, wrapping the QR vending "Get Payment Status" endpoint (`/api/v1/public/vending/status`). Unlike `validate()`, it always returns HTTP 200 — even for unpaid/declined orders — with the outcome in `paymentStatus` (`PAID`/`UNPAID`/`DECLINED`) and an `isPaid()` helper, so polling no longer needs a try/catch to distinguish "not paid yet" from a genuine request failure.
- `client_uri` key in `config/fastpay.php`, backed by `FASTPAY_CLIENT_URI` — the default `clientUri` for `QrData::deepLink()` (see below). Not required unless your app actually calls `deepLink()`.

### Changed

- `QrData::deepLink(string $clientUri, string $orderId)` is now `deepLink(string $orderId, ?string $clientUri = null)` — `$clientUri` defaults to `config('fastpay.client_uri')`, so the common call is just `$qr->deepLink($orderId)`. Pass `$clientUri` explicitly only to override the configured default (e.g. a multi-brand app). Throws `InvalidArgumentException` if neither is set. **Breaking**: the parameter order changed and `$clientUri` moved after `$orderId`.

### Fixed

- `FastpayPaymentService::initiate()` sent `cart` as a nested array inside the JSON request body; FastPay's payment gateway actually requires `cart` to be a **JSON-encoded string** value ("The cart must be a valid JSON string" was the API's own error). Now `json_encode()`d before sending.
- `FastpayQrService::generate()` sent the order id field as `orderID` (capital ID) per a since-corrected assumption about FastPay's casing; the live QR vending API actually expects `orderId` (matching `validate()`/`status()`), and rejected every request with a 400 until this was fixed. The misleading "capitalisation is FastPay's, not a typo" comment was removed along with it.

## [1.0.0] - 2026-07-02

### Added

- **Payment Gateway**: `FastpayPayment::initiate()`, `validate()`, `refund()`, `refundStatus()`.
- **QR Vending**: `FastpayQr::generate()`, `validate()`, `refund()`.
- Mobile deep-link builder (`QrData::deepLink()` / `Nizaamomer\LaravelFastpay\Support\DeepLink`) for Android/iOS/Flutter FastPay-app handoff.
- Typed DTOs (`PaymentInitiationData`, `PaymentValidationData`, `RefundData`, `RefundValidationData`, `QrData`, `CartItem`) and `PaymentStatus` enum for every FastPay response shape.
- Multi-store support via the `stores` array in `config/fastpay.php`.
- Automatic persistence: every initiate/validate/refund call fires an event that upserts into `fastpay_payments`/`fastpay_refunds` — no manual tracking code needed.
- `php artisan fastpay:sync-statuses` — re-validates every pending payment directly against FastPay, since FastPay's IPN only fires for successful payments and never notifies you of failures or abandoned checkouts.
- `payable` polymorphic relation on `FastpayPayment` to link a payment to your own order/subscription models.

### Security

- TLS certificate verification is always enabled and cannot be disabled through this SDK.
- IPN payloads are never trusted — `FastpayPayment::validate()` always re-verifies against FastPay directly.
- FastPay's HTTP-200-with-logical-error-code response shape is normalized: any `code !== 200` is treated as a failure and throws, rather than silently succeeding.
- Order IDs and MSISDNs are format-validated before any request is sent.
- Amounts are validated against FastPay's constraints (greater than zero; 1000 IQD minimum for QR payments) before any request is sent.

[1.0.0]: https://github.com/nizaamomer/laravel-fastpay/releases/tag/v1.0.0

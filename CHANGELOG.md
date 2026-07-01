# Changelog

All notable changes to `nizaamomer/laravel-fastpay` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

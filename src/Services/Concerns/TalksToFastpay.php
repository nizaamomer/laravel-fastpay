<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Services\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Nizaamomer\LaravelFastpay\Exceptions\FastpayException;
use Nizaamomer\LaravelFastpay\Exceptions\FastpayStoreException;

trait TalksToFastpay
{
    /**
     * FastPay's order id contract: 8-32 alphanumeric characters.
     */
    private const ORDER_ID_PATTERN = '/^[A-Za-z0-9]{8,32}$/';

    /**
     * Iraqi MSISDN as FastPay expects it: +964 followed by 9-10 digits.
     */
    private const MSISDN_PATTERN = '/^\+964\d{9,10}$/';

    /**
     * @return array{environment: string, store_id: string, store_password: string, refund_secret_key: ?string}
     */
    private function storeConfig(string $store): array
    {
        $config = config("fastpay.stores.{$store}");

        if (! is_array($config) || empty($config['store_id']) || empty($config['store_password'])) {
            throw FastpayStoreException::unknownStore($store);
        }

        $environment = (string) ($config['environment'] ?? 'staging');

        if (! in_array($environment, ['staging', 'production'], true)) {
            throw FastpayStoreException::unknownEnvironment($store, $environment);
        }

        return [
            'environment' => $environment,
            'store_id' => (string) $config['store_id'],
            'store_password' => (string) $config['store_password'],
            'refund_secret_key' => isset($config['refund_secret_key']) ? (string) $config['refund_secret_key'] : null,
        ];
    }

    /**
     * @param  'pgw'|'qr'  $api
     */
    private function client(string $store, string $api): PendingRequest
    {
        $environment = $this->storeConfig($store)['environment'];
        $baseUrl = (string) config("fastpay.endpoints.{$environment}.{$api}");

        return Http::baseUrl($baseUrl)
            ->timeout((int) config('fastpay.http.timeout', 15))
            ->retry(
                (int) config('fastpay.http.retry_times', 1),
                (int) config('fastpay.http.retry_sleep_ms', 200),
            )
            ->acceptJson()
            ->asJson();
    }

    /**
     * POSTs to FastPay and unwraps its response envelope.
     *
     * FastPay returns HTTP 200 even for logical failures — the real outcome
     * lives in the JSON "code" field, so both transport errors and
     * code !== 200 are treated as failures here.
     *
     * @param  'pgw'|'qr'  $api
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed> the "data" object of a successful response
     */
    private function post(string $store, string $api, string $action, string $path, array $payload): array
    {
        $response = $this->client($store, $api)->post($path, $payload);

        $code = (int) ($response->json('code') ?? $response->status());

        if ($response->failed() || $code !== 200) {
            $messages = (array) ($response->json('messages') ?? []);

            throw FastpayException::requestFailed($action, $code, array_values(array_map('strval', $messages)));
        }

        return (array) $response->json('data');
    }

    private function assertValidOrderId(string $orderId): void
    {
        if (! preg_match(self::ORDER_ID_PATTERN, $orderId)) {
            throw FastpayException::invalidOrderId($orderId);
        }
    }

    private function assertValidMsisdn(string $msisdn): void
    {
        if (! preg_match(self::MSISDN_PATTERN, $msisdn)) {
            throw FastpayException::invalidMsisdn($msisdn);
        }
    }
}

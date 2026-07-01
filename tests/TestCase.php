<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Tests;

use Nizaamomer\LaravelFastpay\FastpayServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            FastpayServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('fastpay.stores.default', [
            'environment' => 'staging',
            'store_id' => 'TESTSTORE01',
            'store_password' => 'TestPassword1',
            'refund_secret_key' => 'test-refund-secret',
        ]);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('cache.default', 'array');
    }
}

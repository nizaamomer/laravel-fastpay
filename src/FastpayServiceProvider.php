<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay;

use Illuminate\Support\Facades\Event;
use Nizaamomer\LaravelFastpay\Console\Commands\SyncFastpayStatuses;
use Nizaamomer\LaravelFastpay\Contracts\FastpayPaymentServiceContract;
use Nizaamomer\LaravelFastpay\Contracts\FastpayQrServiceContract;
use Nizaamomer\LaravelFastpay\Events\PaymentInitiated;
use Nizaamomer\LaravelFastpay\Events\PaymentRefunded;
use Nizaamomer\LaravelFastpay\Events\PaymentValidated;
use Nizaamomer\LaravelFastpay\Listeners\PersistFastpayPayment;
use Nizaamomer\LaravelFastpay\Services\FastpayPaymentService;
use Nizaamomer\LaravelFastpay\Services\FastpayQrService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FastpayServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('fastpay')
            ->hasConfigFile('fastpay')
            ->hasMigrations('create_fastpay_payments_table', 'create_fastpay_refunds_table')
            ->runsMigrations()
            ->hasCommand(SyncFastpayStatuses::class);
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(FastpayPaymentServiceContract::class, FastpayPaymentService::class);
        $this->app->singleton(FastpayQrServiceContract::class, FastpayQrService::class);
    }

    public function packageBooted(): void
    {
        Event::listen(PaymentInitiated::class, [PersistFastpayPayment::class, 'onInitiated']);
        Event::listen(PaymentValidated::class, [PersistFastpayPayment::class, 'onValidated']);
        Event::listen(PaymentRefunded::class, [PersistFastpayPayment::class, 'onRefunded']);
    }
}

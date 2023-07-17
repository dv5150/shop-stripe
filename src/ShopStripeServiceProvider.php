<?php

namespace DV5150\Shop\Stripe;

use DV5150\Shop\Stripe\Console\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ShopStripeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('shop-stripe')
            ->hasConfigFile('shop-stripe')
            ->hasCommand(InstallCommand::class);
    }

    public function register()
    {
        parent::register();

        //
    }

    public function boot()
    {
        parent::boot();

        //
    }
}
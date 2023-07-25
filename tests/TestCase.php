<?php

namespace DV5150\Shop\Stripe\Tests;

use DV5150\Shop\Facades\Shop;
use DV5150\Shop\ShopServiceProvider;
use DV5150\Shop\Stripe\StripeProvider;
use DV5150\Shop\Stripe\Tests\Concerns\RefreshTestDatabase;
use DV5150\Shop\Support\ShopItemCapsule;
use DV5150\Shop\Tests\Mock\Models\PaymentMode;
use DV5150\Shop\Tests\Mock\Models\Product;
use DV5150\Shop\Tests\Mock\Models\ShippingMode;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshTestDatabase;

    protected array $testOrderDataRequired = [
        'personalData' => [
            'email' => 'tester+mailaddress+10000@my-webshop.com',
            'phone' => '+36301001000',
        ],
        'shippingData' => [
            'name' => 'Test Name 1000',
            'zipCode' => '1000',
            'city' => 'Budapest 1000',
            'street' => 'One street 1000',
        ],
        'billingData' => [
            'name' => 'Another Name 9000',
            'zipCode' => '9000',
            'city' => 'Győr 9000',
            'street' => 'Street 9000',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->productClass = config('shop.models.product');

        Shop::registerPaymentProviders([
            StripeProvider::class,
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            ShopServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('shop.models.product', Product::class);
        $app['config']->set('shop.models.paymentMode', PaymentMode::class);
        $app['config']->set('shop.models.shippingMode', ShippingMode::class);

        $app['config']->set('shop.support.shopItemCapsule', ShopItemCapsule::class);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(realpath(__DIR__ . '/../vendor/dv5150/shop/database/migrations'));
    }
}
<?php

namespace DV5150\Shop\Stripe\Tests;

use DV5150\Shop\Contracts\Models\OrderContract;
use DV5150\Shop\Contracts\Models\PaymentModeContract;
use DV5150\Shop\Contracts\Models\SellableItemContract;
use DV5150\Shop\Contracts\Models\ShippingModeContract;
use DV5150\Shop\Contracts\Models\ShopUserContract;
use DV5150\Shop\Facades\Shop;
use DV5150\Shop\ShopServiceProvider;
use DV5150\Shop\Stripe\ShopStripeServiceProvider;
use DV5150\Shop\Stripe\StripeProvider;
use DV5150\Shop\Stripe\Tests\Concerns\RefreshTestDatabase;
use DV5150\Shop\Support\ShopItemCapsule;
use DV5150\Shop\Tests\Mock\Models\PaymentMode;
use DV5150\Shop\Tests\Mock\Models\Product;
use DV5150\Shop\Tests\Mock\Models\ShippingMode;
use DV5150\Shop\Tests\Mock\Models\User;
use Illuminate\Support\Arr;
use Orchestra\Testbench\TestCase as Orchestra;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

class TestCase extends Orchestra
{
    use RefreshTestDatabase;

    protected OrderContract $order;
    protected string $redirectUrl;

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

        $this->saveTheOrder();

        Shop::registerPaymentProviders([
            StripeProvider::class,
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            ShopServiceProvider::class,
            ShopStripeServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('shop.models.product', Product::class);
        $app['config']->set('shop.models.paymentMode', PaymentMode::class);
        $app['config']->set('shop.models.shippingMode', ShippingMode::class);
        $app['config']->set('shop.models.user', User::class);

        $app['config']->set('shop.support.shopItemCapsule', ShopItemCapsule::class);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(realpath(__DIR__ . '/../vendor/dv5150/shop/database/migrations'));
    }

    protected function defineRoutes($router)
    {
        $router->get('/', fn () => 'hello')->name('home');
    }

    protected function saveTheOrder(): void
    {
        /**
         * @var SellableItemContract $productA
         * @var SellableItemContract $productB
         */
        list($productA, $productB) = $this->productClass::factory()
            ->count(2)
            ->create()
            ->all();

        /** @var ShippingModeContract $shippingMode */
        $shippingMode = config('shop.models.shippingMode')::factory()
            ->create();

        /** @var PaymentModeContract $paymentMode */
        $paymentMode = config('shop.models.paymentMode')::factory()
            ->online()
            ->create(['provider' => 'stripe']);

        /** @var ShopUserContract $user */
        $user = config('shop.models.user')::factory()->create();

        actingAs($user);

        $shippingMode->paymentModes()->sync($paymentMode);

        $response = post(route('api.shop.checkout.store'), array_merge($this->testOrderDataRequired, [
            'cartData' => [
                [
                    'item' => ['id' => $productA->getKey()],
                    'quantity' => 2,
                ],
                [
                    'item' => ['id' => $productB->getKey()],
                    'quantity' => 4,
                ],
            ],
            'shippingMode' => [
                'provider' => $shippingMode->getProvider(),
            ],
            'paymentMode' => [
                'provider' => 'stripe',
            ],
        ]));

        $this->order = config('shop.models.order')::latest()->first();
        $this->redirectUrl = Arr::get(json_decode($response->getContent(), true), 'redirectUrl');
    }
}
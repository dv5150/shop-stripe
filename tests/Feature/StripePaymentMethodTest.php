<?php

namespace DV5150\Shop\Stripe\Tests\Feature;

use DV5150\Shop\Contracts\Models\PaymentModeContract;
use DV5150\Shop\Contracts\Models\SellableItemContract;
use DV5150\Shop\Contracts\Models\ShippingModeContract;
use function Pest\Laravel\post;

it('redirects to the external payment service', function () {
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

    dd($response->getContent());
});
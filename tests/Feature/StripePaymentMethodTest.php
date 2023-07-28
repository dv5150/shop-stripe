<?php

namespace DV5150\Shop\Stripe\Tests\Feature;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('redirects to the external payment service', function () {
    get($this->redirectUrl)->assertRedirectContains('https://checkout.stripe.com');
});

it('receives webhook from stripe and saves a payment record for the order', function () {
    $testResponse = json_decode(File::get(__DIR__.'/../test_response.json'), true);

    Arr::set($testResponse, 'data.object.metadata.order_id', $this->order->getKey());

    post(route('api.shop.payment.webhook', [
        'paymentProvider' => 'stripe'
    ]), $testResponse);

    expect(
        config('shop.models.payment')::query()
            ->where('intent_id', Arr::get($testResponse, 'data.object.payment_intent'))
            ->where('order_id', $this->order->getKey())
            ->exists()
    )->toBeTrue();
});
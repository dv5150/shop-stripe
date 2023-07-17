<?php

namespace DV5150\Shop\Stripe;

use DV5150\Shop\Contracts\Models\OrderContract;
use DV5150\Shop\Contracts\Models\OrderItemContract;
use DV5150\Shop\Contracts\Support\PaymentProviderContract;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Stripe\StripeClientInterface;
use Stripe\Webhook;
use Throwable;

class StripeProvider implements PaymentProviderContract
{
    protected StripeClientInterface $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(
            app()->isProduction()
                ? config('shop-stripe.live.secret')
                : config('shop-stripe.sandbox.secret')
        );
    }

    public static function getProvider(): string
    {
        return 'stripe';
    }

    public static function getName(): string
    {
        return 'Stripe';
    }

    public static function isOnlinePayment(): bool
    {
        return true;
    }

    public function pay(OrderContract $order)
    {
        return redirect()->away(
            $this->stripe->checkout->sessions->create([
                'mode' => 'payment',
                'success_url' => route('home'),
                'cancel_url' => route('home'),
                'customer_email' => $order->getEmail(),
                'line_items' => $this->createProductArray($order),
                'metadata' => [
                    'order_id' => $order->getKey(),
                ]
            ])->url
        );
    }

    public function webhook(Request $request)
    {
        try {
            Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                config('shop-stripe.sandbox.webhookSecret')
            );
        } catch (Throwable $e) {
            report($e);
            abort(400, $e->getMessage());
        }

        if ($this->paymentSucceeded($request)) {
            config('shop.models.payment')::create([
                'provider' => 'stripe',
                'order_id' => $request->input('data.object.metadata.order_id'),
                'intent_id' => $request->input('data.object.payment_intent'),
            ]);
        }
    }

    protected function paymentSucceeded(Request $request): bool
    {
        return ($request->input('type') === 'checkout.session.completed')
            && ($request->input('data.object.payment_status') === 'paid');
    }

    protected function createProductArray(OrderContract $order): array
    {
        return $order->items->map(fn (OrderItemContract $orderItem) => [
            'price_data' => [
                'currency' => config('shop.currency.code'),
                'product_data' => [
                    'name' => $orderItem->getName(),
                ],
                'unit_amount' => $orderItem->getPriceGross() * config('shop-stripe.currencyMultiplier'),
            ],
            'quantity' => $orderItem->getQuantity(),
        ])->all();
    }
}

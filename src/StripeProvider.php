<?php

namespace DV5150\Shop\Stripe;

use DV5150\Shop\Contracts\Models\OrderContract;
use DV5150\Shop\Contracts\Models\OrderItemContract;
use DV5150\Shop\Contracts\Support\PaymentProviderContract;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Stripe\Checkout\Session;
use Stripe\Coupon;
use Stripe\Stripe;
use Stripe\Webhook;
use Throwable;

class StripeProvider implements PaymentProviderContract
{
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
        Stripe::setApiKey(
            app()->isProduction()
                ? config('shop-stripe.live.secret')
                : config('shop-stripe.sandbox.secret')
        );

        return redirect()->away(
            Session::create($this->buildCheckoutData($order))->url
        );
    }

    public function webhook(Request $request)
    {
        if (! app()->runningUnitTests()) {
            try {
                Webhook::constructEvent(
                    $request->getContent(),
                    $request->header('Stripe-Signature'),
                    app()->isProduction()
                        ? config('shop-stripe.live.webhookSecret')
                        : config('shop-stripe.sandbox.webhookSecret')
                );
            } catch (Throwable $e) {
                report($e);
                abort(400, $e->getMessage());
            }
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

    protected function buildCheckoutData(OrderContract $order): array
    {
        $data = [
            'mode' => 'payment',
            'line_items' => $this->createProductListing($order),
            'currency' => config('shop.currency.code'),
            'success_url' => route('home'),
            'cancel_url' => route('home'),
            'customer_email' => $order->getEmail(),
            'metadata' => [
                'order_id' => $order->getKey(),
            ],
        ];

        /** @var OrderItemContract $coupon */
        if ($coupon = $order->items()->whereType('coupon')->first()) {
            $coupon = Coupon::create([
                'amount_off' => abs($coupon->getPriceGross()) * 100,
                'duration' => 'once',
                'currency' => config('shop.currency.code'),
            ]);

            Arr::set($data, 'discounts', [
                ['coupon' => "{$coupon->id}"]
            ]);
        }

        return $data;
    }

    protected function createProductListing(OrderContract $order): array
    {
        return $order->items()
            ->where('type', '!=', 'coupon')
            ->get()
            ->map(fn (OrderItemContract $orderItem) => [
                'price_data' => [
                    'currency' => config('shop.currency.code'),
                    'product_data' => [
                        'name' => $orderItem->getItemName(),
                    ],
                    'unit_amount' => $orderItem->getPriceGross() * 100,
                ],
                'quantity' => $orderItem->getQuantity(),
            ])->all();
    }
}

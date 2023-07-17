<?php

return [
    'sandbox' => [
        'secret' => env('SHOP_STRIPE_SANDBOX_SECRET'),
        'webhookSecret' => env('SHOP_STRIPE_SANDBOX_WEBHOOK_SECRET'),
    ],
    'live' => [
        'secret' => env('SHOP_STRIPE_LIVE_SECRET'),
        'webhookSecret' => env('SHOP_STRIPE_LIVE_WEBHOOK_SECRET'),
    ],
    'currencyMultiplier' => 100 // integer: 100 for HUF, 1 for EUR and USD
];
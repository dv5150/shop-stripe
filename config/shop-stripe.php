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
];
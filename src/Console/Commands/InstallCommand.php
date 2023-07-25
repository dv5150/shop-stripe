<?php

namespace DV5150\Shop\Stripe\Console\Commands;

use DV5150\Shop\Stripe\StripeProvider;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'shop:install:stripe';

    protected $description = 'Install the Stripe payment method for Laravel Webshop package.';

    protected array $todos = [
        'Please update the shop-stripe.php config file with your Stripe access keys.',
        'Please update the price and active status of your newly created Payment Mode entity.',
    ];

    public function handle()
    {
        $this->installConfig();
        $this->installPaymentMethod();

        $this->afterHandle();

        return self::SUCCESS;
    }

    protected function installConfig(): void
    {
        $this->info('Publishing config file...');

        $this->callSilently("vendor:publish", [
            '--tag' => "shop-stripe-config",
        ]);
    }

    protected function installPaymentMethod(): void
    {
        $this->info('Creating Stripe payment method entity...');

        config('shop.models.paymentMode')::create([
            'provider' => StripeProvider::getProvider(),
            'name' => StripeProvider::getName(),
            'is_online_payment' => StripeProvider::isOnlinePayment(),
            'price_gross' => 0.0,
            'is_active' => false,
        ]);
    }

    protected function afterHandle(): void
    {
        $this->info('Installation complete.');

        for ($i = 1; $i <= $count = count($this->todos); $i++) {
            $this->warn("[$i/$count] {$this->todos[$i-1]}");
        }
    }
}

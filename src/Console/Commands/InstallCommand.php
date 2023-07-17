<?php

namespace DV5150\Shop\Stripe\Console\Commands;

use DV5150\Shop\Stripe\StripeProvider;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'shop:install:stripe';

    protected $description = 'Install the Stripe payment method for Laravel shop package.';

    protected array $todoAfterInstall = [
        'Please update the shop-stripe.php config file with your Stripe access keys.',
        'Please update the price and active status of your newly created Payment Mode entity.',
    ];

    public function handle()
    {
        $this->info('Publishing config file...');

        $this->callSilently("vendor:publish", [
            '--tag' => "shop-stripe-config",
        ]);

        $this->info('Creating matching payment method entity...');

        config('shop.models.paymentMode')::create([
            'provider' => StripeProvider::getProvider(),
            'name' => StripeProvider::getName(),
            'is_online_payment' => StripeProvider::isOnlinePayment(),
            'price_gross' => 0.0,
            'is_active' => false,
        ]);

        $this->info('Installation complete.');

        $this->displayWarningMessages();

        return self::SUCCESS;
    }

    protected function displayWarningMessages(): void
    {
        $count = count($this->todoAfterInstall);

        for ($i = 1; $i <= $count; $i++) {
            $this->warn("[$i/$count] {$this->todoAfterInstall[$i-1]}");
        }
    }
}

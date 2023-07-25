# Laravel Webshop Stripe Support</span>

---
## The goal of the project:
To provide [Stripe](https://stripe.com/docs) payment integration for the [Laravel Webshop package](https://github.com/dv5150/shop).

---

## Requirements
- Laravel 8+
- PHP >=8.1

---

## Setup
1. `$ composer require "dv5150/shop-stripe"`
2. Set up `config/shop-stripe.php` config file with your Stripe access keys.
3. Set the `active` status of your newly created Stripe `PaymentMode` entity to true. Do not change to `provider` field's value.
4. Attach the Stripe `PaymentMode` entity to the desired `ShippingMode` entities.
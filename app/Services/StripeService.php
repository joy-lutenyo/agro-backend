<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripeService
{
    const KES_TO_USD_RATE = 150; // example rate

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    // 👇 THIS IS THE METHOD YOU WILL USE
    public function createPaymentIntentFromKes(float $kesAmount)
    {
        if ($kesAmount <= 0) {
            throw new \Exception('Invalid amount');
        }

        // 1️⃣ Convert KES → USD
        $usdAmount = round($kesAmount / self::KES_TO_USD_RATE, 2);

        // 2️⃣ Convert USD → cents for Stripe
        $stripeAmount = (int) round($usdAmount * 100);

        // 3️⃣ Create Stripe PaymentIntent
        return PaymentIntent::create([
            'amount' => $stripeAmount,
            'currency' => 'usd',
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }
}

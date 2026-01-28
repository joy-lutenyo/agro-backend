<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature failed');
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook payload invalid');
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // ✅ Handle successful payment
        if ($event->type === 'payment_intent.succeeded') {

            $paymentIntent = $event->data->object;
            $clientSecret = $paymentIntent->client_secret;

            $order = Order::where('stripe_client_secret', $clientSecret)->first();

            if ($order) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                ]);
            }
        }

        // ❌ Handle failed payment
        if ($event->type === 'payment_intent.payment_failed') {

            $paymentIntent = $event->data->object;
            $clientSecret = $paymentIntent->client_secret;

            $order = Order::where('stripe_client_secret', $clientSecret)->first();

            if ($order) {
                $order->update([
                    'payment_status' => 'failed',
                    'status' => 'cancelled',
                ]);
            }
        }

        return response()->json(['status' => 'ok'], 200);
    }
}

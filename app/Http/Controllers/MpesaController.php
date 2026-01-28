<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

class MpesaController extends Controller
{
    private $shortcode;
    private $passkey;
    private $consumerKey;
    private $consumerSecret;
    private $callbackUrl;

    public function __construct()
    {
        $this->shortcode = env('MPESA_SHORTCODE');
        $this->passkey = env('MPESA_PASSKEY');
        $this->consumerKey = env('MPESA_CONSUMER_KEY');
        $this->consumerSecret = env('MPESA_CONSUMER_SECRET');
        $this->callbackUrl = env('MPESA_CALLBACK_URL');
    }

    private function getAccessToken()
    {
        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->get('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');

        return $response->json()['access_token'];
    }

    public function stkPush(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|numeric',
            'order_id' => 'required|integer',
        ]);

        $phone = $request->phone;
        $amount = $request->amount;
        $orderId = $request->order_id;

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $payload = [
            "BusinessShortCode" => $this->shortcode,
            "Password" => $password,
            "Timestamp" => $timestamp,
            "TransactionType" => "CustomerPayBillOnline",
            "Amount" => $amount,
            "PartyA" => $phone,
            "PartyB" => $this->shortcode,
            "PhoneNumber" => $phone,
            "CallBackURL" => $this->callbackUrl,
            "AccountReference" => "Order#{$orderId}",
            "TransactionDesc" => "Payment for AgroApp Order",
        ];

        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->post('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest', $payload);

        // Save checkout_request_id in order table for tracking callback
        $checkoutRequestID = $response->json()['CheckoutRequestID'] ?? null;
        if ($checkoutRequestID) {
            DB::table('orders')->where('id', $orderId)->update([
                'checkout_request_id' => $checkoutRequestID,
                'payment_status' => 'pending',
            ]);
        }

        return response()->json($response->json());
    }

    public function callback(Request $request)
    {
        Log::info('MPESA CALLBACK: ' . json_encode($request->all()));

        $body = $request->Body ?? null;
        if (!$body) return response()->json(['status' => 'no data']);

        $stkCallback = $body['stkCallback'] ?? null;
        if (!$stkCallback) return response()->json(['status' => 'no stkCallback']);

        $checkoutRequestID = $stkCallback['CheckoutRequestID'] ?? null;
        $resultCode = $stkCallback['ResultCode'] ?? 0;

        // Successful payment
        if ($resultCode == 0) {
            $items = $stkCallback['CallbackMetadata']['Item'];
            $amount = $items[0]['Value'];
            $mpesaReceipt = $items[1]['Value'];
            $phone = $items[4]['Value'];

            // ✅ Update order in MySQL
            $order = Order::where('checkout_request_id', $checkoutRequestID)->first();
            if ($order) {
                $order->payment_status = 'paid';
                $order->status = 'confirmed';
                $order->mpesa_receipt = $mpesaReceipt;
                $order->save();

                // ✅ Reduce stock for each product in the order
                $orderItems = OrderItem::where('order_id', $order->id)->get();
                foreach ($orderItems as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->stock = max(0, $product->stock - $item->quantity);
                        $product->save();
                    }
                }
            }

            return response()->json(['status' => 'success']);
        }

        // Failed or canceled payment
        return response()->json(['status' => 'failed', 'data' => $stkCallback]);
    }
}

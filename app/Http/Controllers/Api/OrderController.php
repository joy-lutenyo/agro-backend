<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\CartItem;
use App\Models\Address;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlaced;
use App\Services\StripeService;


class OrderController extends Controller
{
    /**
     * Get all orders for authenticated user
     *
     * Logic:
     * 1. Fetch orders belonging to authenticated user
     * 2. Include address details
     * 3. Order by newest first
     */
 public function index(Request $request)
{
    try {
        $user = $request->user();

        $query = Order::with([
            'address',
            'user',        // IMPORTANT for Flutter
        ])->orderBy('created_at', 'desc');

        // Buyers only see their own orders
        if ($user->role === 'buyer') {
            $query->where('user_id', $user->id);
        }

        $orders = $query->get();

        return response()->json($orders, 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error fetching orders',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Create a new order (checkout)
     *
     * Logic:
     * 1. Validate request
     * 2. Fetch cart items
     * 3. Calculate total price
     * 4. Create order
     * 5. Clear cart
     */
    
public function store(Request $request, StripeService $stripe)
{
    $user = $request->user();

    $validated = $request->validate([
        'address_id' => 'required|exists:addresses,id',
        'payment_method' => 'required|in:Stripe,Mpesa,Cash on Delivery',
    ]);

    $cartItems = CartItem::with('product')
        ->where('user_id', $user->id)
        ->get();

    if ($cartItems->isEmpty()) {
        return response()->json(['message' => 'Cart is empty'], 400);
    }

    $items = [];
    $totalPriceKes = 0;

    foreach ($cartItems as $item) {
        $itemTotal = $item->product->price * $item->quantity;
        $totalPriceKes += $itemTotal;

        $items[] = [
            'product_id' => $item->product->id,
            'name' => $item->product->name,
            'price' => $item->product->price,
            'quantity' => $item->quantity,
            'total' => $itemTotal,
            'image_url' => $item->product->image_url,
        ];
    }

    DB::beginTransaction();

    try {
        $order = Order::create([
            'user_id' => $user->id,
            'address_id' => $validated['address_id'],
            'items' => $items,
            'total_price' => $totalPriceKes, // KES
            'payment_method' => $validated['payment_method'],
            'payment_status' => $validated['payment_method'] === 'Cash on Delivery'
    ? 'pending'
    : 'pending',

'status' => $validated['payment_method'] === 'Cash on Delivery'
    ? 'confirmed'
    : 'pending_payment',

        ]);

        $response = [
            'order' => $order->load('address'),
        ];

        // ✅ OPTION B: Stripe only
        if ($validated['payment_method'] === 'Stripe') {

    $exchangeRate = 150;
    $usdAmount = round($totalPriceKes / $exchangeRate, 2);

    if ($usdAmount < 0.5) {
        throw new \Exception('Amount too small for Stripe');
    }

    $paymentIntent = $stripe->createPaymentIntent((int) ($usdAmount * 100));

    $order->update([
        'stripe_payment_intent_id' => $paymentIntent->id,
        'stripe_client_secret' => $paymentIntent->client_secret,
    ]);

    $response['amount_kes'] = $totalPriceKes;
    $response['amount_usd'] = $usdAmount;
    $response['client_secret'] = $paymentIntent->client_secret;
}


        // Clear cart
        CartItem::where('user_id', $user->id)->delete();

        DB::commit();

        Mail::to($user->email)->send(new OrderPlaced($order));

        return response()->json($response, 201);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Error placing order',
            'error' => $e->getMessage(),
        ], 500);
    }
}


    /**
     * Get single order details
     */
   public function show(Request $request, $id)
{
    try {
        $user = $request->user();

        $query = Order::with(['address','user'])
            ->where('id', $id);

        if ($user->role === 'buyer') {
            $query->where('user_id', $user->id);
        }

        $order = $query->firstOrFail();

        return response()->json($order, 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Order not found',
        ], 404);
    }
}


    /**
 * Cancel order 
 */
public function cancel(Request $request, $id)
{
    try {
        $user = $request->user();

        // 🚫 Only buyers can cancel orders
        if ($user->role !== 'buyer') {
            return response()->json([
                'message' => 'Only buyers can cancel orders'
            ], 403);
        }

        // 🔍 Buyer can only cancel their own order
        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // ⛔ Check order status
       if (!in_array($order->status, ['confirmed', 'pending_payment'])) {

            return response()->json([
                'message' => 'Order cannot be cancelled'
            ], 400);
        }

        // ✅ Cancel order
        $order->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error cancelling order',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Mark an order as delivered
 */
public function markDelivered(Request $request, $id)
{
    try {
        $user = $request->user();

        // Only admin or farmer can mark orders delivered
        if (!in_array($user->role, ['admin', 'farmer'])) {
            return response()->json([
                'message' => 'Only admins or farmers can mark orders as delivered'
            ], 403);
        }

        $order = Order::findOrFail($id);

        // Only allow if order is not already delivered or cancelled
        if (in_array($order->status, ['delivered', 'cancelled'])) {
            return response()->json([
                'message' => 'Order cannot be updated'
            ], 400);
        }

        $order->update([
            'status' => 'delivered'
        ]);

        return response()->json([
            'message' => 'Order marked as delivered',
            'order' => $order
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error updating order',
            'error' => $e->getMessage()
        ], 500);
    }
}


}

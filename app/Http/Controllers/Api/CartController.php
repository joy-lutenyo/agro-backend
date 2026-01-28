<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CartItem;
use App\Models\Product;

class CartController extends Controller
{
    /**
     * Get all cart items for user
     * 
     * Logic:
     * 1. Fetch cart items for the user
     * 2. Include product details using eager loading
     * 3. Return cart with product info
     */
    public function index(Request $request)
{
    $userId = auth()->id();

    try {
        $cartItems = CartItem::with('product')
            ->where('user_id', $userId)
            ->get();

        return response()->json($cartItems, 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error fetching cart',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Add product to cart
     * 
     * Logic:
     * 1. Check if product exists
     * 2. Check if already in cart
     * 3. If yes, increase quantity
     * 4. If no, create new cart item
     */
  public function store(Request $request)
{
    $validated = $request->validate([
        'product_id' => 'required|integer|exists:products,id',
        'quantity' => 'required|integer|min:1',
    ]);

    try {
        $userId = auth()->id();

        $cartItem = CartItem::where('user_id', $userId)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($cartItem) {
            $cartItem->increment('quantity', $validated['quantity']);
        } else {
            $cartItem = CartItem::create([
                'user_id' => $userId,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
            ]);
        }

        return response()->json([
            'message' => 'Product added to cart successfully',
            'cart_item' => $cartItem->load('product')
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error adding to cart',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Update cart item quantity
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $cartItem = CartItem::findOrFail($id);
            $cartItem->update(['quantity' => $validated['quantity']]);

            return response()->json([
                'message' => 'Cart updated successfully',
                'cart_item' => $cartItem->load('product')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function destroy($id)
    {
        try {
            $cartItem = CartItem::findOrFail($id);
            $cartItem->delete();

            return response()->json([
                'message' => 'Item removed from cart'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error removing item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear entire cart
     * 
     * Logic:
     * Delete all cart items for the user
     */
    public function clear(Request $request)
    {
        try {
           CartItem::where('user_id', auth()->id())->delete();


            return response()->json([
                'message' => 'Cart cleared successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error clearing cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
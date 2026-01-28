<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address;

class AddressController extends Controller
{
    /**
     * Get all addresses for user
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            $addresses = Address::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($addresses, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching addresses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new address
     * 
     * Logic:
     * 1. Validate address data
     * 2. Associate with authenticated user
     * 3. Save to database
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'house' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'required|string|max:20',
        ]);

        try {
            $user = $request->user();

            $address = Address::create([
                'user_id' => $user->id,
                'house' => $validated['house'],
                'street' => $validated['street'],
                'city' => $validated['city'],
                'postal_code' => $validated['postal_code'] ?? null,
                'phone' => $validated['phone'],
            ]);

            return response()->json([
                'message' => 'Address added successfully',
                'address' => $address
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error adding address',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update address
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'house' => 'sometimes|string|max:255',
            'street' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'sometimes|string|max:20',
        ]);

        try {
            $user = $request->user();
            
            // Ensure user owns this address
            $address = Address::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $address->update($validated);

            return response()->json([
                'message' => 'Address updated successfully',
                'address' => $address
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating address',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete address
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $address = Address::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $address->delete();

            return response()->json([
                'message' => 'Address deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting address',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
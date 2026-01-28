<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FarmerOnly
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 🚫 Not logged in
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // 🚫 Not a farmer
        if ($user->role !== 'farmer') {
            return response()->json([
                'message' => 'Access denied. Farmers only.'
            ], 403);
        }

        // ✅ Farmer allowed
        return $next($request);
    }
}

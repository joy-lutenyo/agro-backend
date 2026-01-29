<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\CropController;
use App\Http\Controllers\Api\SocialAuthController;



/*
|--------------------------------------------------------------------------
| AUTH ROUTES (PUBLIC)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Google OAuth Routes
Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'callback']);




/*
|--------------------------------------------------------------------------
| PRODUCT ROUTES (PUBLIC READ)
|--------------------------------------------------------------------------
*/

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

/*
|--------------------------------------------------------------------------
| CROPS ROUTES (PUBLIC READ)
|--------------------------------------------------------------------------
*/
Route::get('/crops', [CropController::class, 'index']);
Route::get('/crops/{id}', [CropController::class, 'show']);

/*
|--------------------------------------------------------------------------
| STRIPE WEBHOOK (PUBLIC)
|--------------------------------------------------------------------------
*/
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (AUTH REQUIRED)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Current user
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });

    /*
    |--------------------------------------------------------------------------
    | PRODUCTS (FARMERS / ADMIN)
    |--------------------------------------------------------------------------
    */
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | CROPS (FARMERS / ADMIN)
    |--------------------------------------------------------------------------
    */
    Route::post('/crops', [CropController::class, 'store']);
    Route::put('/crops/{id}', [CropController::class, 'update']);
    Route::delete('/crops/{id}', [CropController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | CART ROUTES
    |--------------------------------------------------------------------------
    */
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::post('/cart/clear', [CartController::class, 'clear']);

    /*
    |--------------------------------------------------------------------------
    | ADDRESS ROUTES
    |--------------------------------------------------------------------------
    */
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | ORDER ROUTES
    |--------------------------------------------------------------------------
    */
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    Route::patch('/orders/{id}/deliver', [OrderController::class, 'markDelivered']);

   

   
    
    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [AuthController::class, 'logout']);
});

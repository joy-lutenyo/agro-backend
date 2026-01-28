<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     * Creates orders table to track customer purchases
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Customer who placed order
            $table->unsignedBigInteger('address_id'); // Delivery address
            $table->json('items'); // Order items stored as JSON (snapshot of cart)
            $table->decimal('total_price', 10, 2); // Total order amount
            $table->string('payment_method'); // "Cash on Delivery", "Mpesa", "Card"
            $table->string('payment_status')->default('pending'); // "pending", "paid", "failed"
            $table->string('status')->default('confirmed'); // Order status for tracking
            $table->string('checkout_request_id')->nullable(); // For M-Pesa tracking
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
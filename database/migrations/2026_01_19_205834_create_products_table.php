<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     * Creates the products table to store farm products
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Farmer who added the product
            $table->string('name'); // Product name (e.g., "Fresh Tomatoes")
            $table->text('description')->nullable(); // Product description
            $table->string('image_url'); // Cloudinary URL for product image
            $table->decimal('price', 10, 2); // Price with 2 decimal places
            $table->string('unit')->default('piece'); // Unit of measurement (kg, piece, bag, etc.)
            $table->integer('stock')->default(0); // Available quantity in stock
            $table->integer('quantity')->default(1); // Default quantity when adding to cart
            $table->decimal('rating', 3, 2)->default(0.00); // Rating out of 5
            $table->string('category')->default('general'); // Product category
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
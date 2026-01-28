<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
    ];

    /**
     * Cast to integer
     */
    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Relationship: Cart item belongs to a User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Cart item belongs to a Product
     * This allows us to fetch product details with the cart
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
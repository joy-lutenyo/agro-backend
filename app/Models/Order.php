<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'user_id',
        'address_id',
        'items',
        'total_price',
        'payment_method',
        'payment_status',
        'status',
        'checkout_request_id',
    ];

    /**
     * Cast attributes
     * 'items' is stored as JSON in database
     */
    protected $casts = [
        'items' => 'array', // Automatically encode/decode JSON
        'total_price' => 'decimal:2',
    ];

    /**
     * Relationship: Order belongs to a User (customer)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Order has a delivery Address
     */
    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * These fields can be filled using Product::create([...])
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'image_url',
        'price',
        'unit',
        'rating',
        'category',
        'stock',
        'quantity',
    ];

    /**
     * Cast attributes to specific types
     */
    protected $casts = [
        'price' => 'float', // Always return price with 2 decimals
        'rating' => 'float',
        'stock' => 'integer',
        'quantity' => 'integer',
    ];

    /**
     * Relationship: Product belongs to a User (the farmer)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Product can be in many cart items
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
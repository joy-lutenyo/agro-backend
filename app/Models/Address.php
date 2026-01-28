<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'user_id',
        'house',
        'street',
        'city',
        'postal_code',
        'phone',
    ];

    /**
     * Relationship: Address belongs to a User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Address can have many orders
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
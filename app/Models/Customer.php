<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A shopper (1:1 with users: a customer IS a user who shops; staff have no customer row).
 * Stores nothing but the link. Addresses, orders and reviews point at the customer; the membership
 * tier and total spent are calculated from the orders (see User).
 */
class Customer extends Model
{
    protected $fillable = ['user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true)->whereNull('archived_at');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    /** The customer row of this user, made the first time they shop. */
    public static function idForUser(int $userId): int
    {
        return static::firstOrCreate(['user_id' => $userId])->id;
    }
}

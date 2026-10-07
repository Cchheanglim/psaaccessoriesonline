<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A delivery address with its map pin. Orders point at the address they ship to, so an address used by
 * an order is never edited: "edit" saves a new address and archives the old one (archived_at).
 */
class CustomerAddress extends Model
{
    protected $fillable = ['customer_id', 'label', 'recipient_name', 'phone', 'address_line', 'latitude', 'longitude', 'is_default', 'archived_at'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_default' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'address_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

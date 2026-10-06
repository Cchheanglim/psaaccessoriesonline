<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A saved delivery address with its map pin (latitude + longitude). One can be the default. */
class CustomerAddress extends Model
{
    protected $fillable = ['customer_id', 'label', 'recipient_name', 'phone', 'address_line', 'latitude', 'longitude', 'is_default'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_default' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

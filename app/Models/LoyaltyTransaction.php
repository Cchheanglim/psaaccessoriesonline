<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One change to a customer's points: earn (delivered order), redeem (discount), adjust (staff) or expire. */
class LoyaltyTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['customer_id', 'order_id', 'handled_by', 'type', 'points', 'note'];

    protected $casts = ['points' => 'integer'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}

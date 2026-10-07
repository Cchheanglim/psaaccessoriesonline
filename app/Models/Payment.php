<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One payment attempt for an order (a rejected slip, then a new one, are two rows).
 * status: pending, slip_uploaded, paid_demo, verified, failed, refunded.
 */
class Payment extends Model
{
    protected $fillable = ['order_id', 'payment_method_id', 'amount_usd', 'status', 'slip_url', 'verified_by', 'paid_at'];

    protected $attributes = ['status' => 'pending'];

    protected $casts = [
        'amount_usd' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}

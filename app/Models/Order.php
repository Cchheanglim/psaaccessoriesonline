<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_name',
        'customer_phone',
        'delivery_address',
        'delivery_notes',
        'latitude',
        'longitude',
        'subtotal_usd',
        'subtotal_khr',
        'delivery_fee_usd',
        'delivery_fee_khr',
        'total_usd',
        'total_khr',
        'payment_method', // 'bakong_khqr', 'aba_pay', 'cod'
        'payment_status', // 'pending', 'slip_uploaded', 'verified', 'failed'
        'order_status',   // 'pending_payment', 'processing', 'out_for_delivery', 'delivered', 'cancelled'
        'payment_slip_url',
        'paid_at',
    ];

    protected $casts = [
        'subtotal_usd' => 'decimal:2',
        'subtotal_khr' => 'integer',
        'delivery_fee_usd' => 'decimal:2',
        'delivery_fee_khr' => 'integer',
        'total_usd' => 'decimal:2',
        'total_khr' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}

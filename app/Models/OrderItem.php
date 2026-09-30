<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_title',
        'product_image',
        'price_usd',
        'price_khr',
        'quantity',
        'total_usd',
        'total_khr',
    ];

    protected $casts = [
        'price_usd' => 'decimal:2',
        'price_khr' => 'integer',
        'quantity' => 'integer',
        'total_usd' => 'decimal:2',
        'total_khr' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

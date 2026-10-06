<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One product line on an order. Title, picture and SELL price are copied at checkout, so old orders
 * and receipts stay correct when the product changes; unit_cost_usd (BUY cost) makes profit possible.
 */
class OrderItem extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_title',
        'product_image',
        'quantity',
        'unit_price_usd',
        'unit_cost_usd',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_usd' => 'decimal:2',
        'unit_cost_usd' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function lineTotal(): float
    {
        return round((float) $this->unit_price_usd * $this->quantity, 2);
    }
}

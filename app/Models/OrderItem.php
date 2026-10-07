<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One product line on an order. The name and picture come from the product (products are archived,
 * never deleted). unit_price_usd is the price agreed in this sale and unit_cost_usd the buy cost at
 * that time: facts of the sale, not copies, because the product's current price and cost change.
 */
class OrderItem extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'product_id',
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

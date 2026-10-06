<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * How many are on the shelf and what they cost to BUY (1:1 with products).
 * quantity_on_hand is a quick copy of the sum of stock_movements; change it through App\Support\Inventory.
 */
class ProductStock extends Model
{
    public const CREATED_AT = null;

    protected $fillable = ['product_id', 'quantity_on_hand', 'low_stock_threshold', 'average_cost_usd'];

    protected $attributes = [
        'quantity_on_hand' => 0,
        'low_stock_threshold' => 5,
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'low_stock_threshold' => 'integer',
        'average_cost_usd' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function isLow(): bool
    {
        return $this->quantity_on_hand <= $this->low_stock_threshold;
    }
}

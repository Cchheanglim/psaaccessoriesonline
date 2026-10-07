<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One product on a purchase order and how much the shop BUYS it for. */
class PurchaseOrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['purchase_order_id', 'product_id', 'quantity', 'unit_cost_usd'];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost_usd' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

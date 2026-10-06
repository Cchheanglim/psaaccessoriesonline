<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One change to stock: purchase (+), sale (-), return (+), adjust (+/-) or damage (-), with buy cost and sell price. */
class StockMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'product_id', 'handled_by', 'type', 'quantity_change', 'unit_cost_usd', 'unit_price_usd',
        'order_item_id', 'purchase_order_item_id', 'reason',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'unit_cost_usd' => 'decimal:2',
        'unit_price_usd' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }
}

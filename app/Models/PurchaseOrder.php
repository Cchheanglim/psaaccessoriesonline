<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An order the shop places with a supplier: draft, then ordered, then received (or cancelled). */
class PurchaseOrder extends Model
{
    protected $fillable = ['po_number', 'supplier_id', 'ordered_by', 'status', 'ordered_at', 'received_at', 'total_cost_usd'];

    protected $casts = [
        'ordered_at' => 'datetime',
        'received_at' => 'datetime',
        'total_cost_usd' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function orderedBy()
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}

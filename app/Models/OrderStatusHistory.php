<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One status change on an order: which status, who changed it, when. */
class OrderStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'order_status_history';

    protected $fillable = ['order_id', 'order_status_id', 'changed_by', 'note'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function status()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

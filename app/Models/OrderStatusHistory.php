<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * One status change on an order: which status (order_status_id), who changed it, when.
 * $history->status reads and writes the status code ('delivered', ...) through order_statuses.
 */
class OrderStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'order_status_history';

    protected $fillable = ['order_id', 'order_status_id', 'status', 'changed_by', 'note'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /** The status code, e.g. "delivered". */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn () => OrderStatus::codeFor($this->order_status_id),
            set: fn (string $code) => ['order_status_id' => OrderStatus::idFor($code)],
        );
    }
}

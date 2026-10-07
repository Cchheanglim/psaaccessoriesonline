<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A chat message about one order. sender_id is the customer's or a staff member's account;
 * a message is "from staff" when it was not sent by the order's customer.
 */
class OrderMessage extends Model
{
    protected $fillable = [
        'order_id',
        'sender_id',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** Messages the order's customer wrote. */
    public function scopeFromCustomer(Builder $query): Builder
    {
        return $query->whereHas('order', fn ($q) => $q->whereColumn('orders.user_id', 'order_messages.sender_id'));
    }

    /** Messages the shop wrote (anyone other than the order's customer). */
    public function scopeFromStaff(Builder $query): Builder
    {
        return $query->whereDoesntHave('order', fn ($q) => $q->whereColumn('orders.user_id', 'order_messages.sender_id'));
    }

    public function isFromStaff(Order $order): bool
    {
        return $this->sender_id === null || $this->sender_id !== $order->user_id;
    }
}

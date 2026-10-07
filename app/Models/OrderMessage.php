<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A chat message about one order. sender_id is the customer's or a staff member's account;
 * a message is "from staff" when it was not sent by the order's customer.
 * Deleting keeps the row: deleted_at says when, deleted_by who. Deleted messages are left out of
 * every query (counts, unread, the bell) unless asked for with withTrashed().
 */
class OrderMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'sender_id',
        'body',
        'read_at',
        'deleted_by',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * The chat clean-up (shop rules in site_settings):
     * 1. a chat with no new message for chat.auto_delete_days is deleted (deleted_by empty = automatically);
     * 2. a message deleted more than chat.purge_days ago is removed for good.
     * Returns [chat messages deleted, messages removed for good].
     */
    public static function cleanUp(): array
    {
        $quietSince = now()->subDays((int) SiteSetting::number('chat.auto_delete_days'));
        $quietChats = static::query()->select('order_id')->groupBy('order_id')
            ->havingRaw('MAX(created_at) < ?', [$quietSince->toDateTimeString()])->pluck('order_id');
        $deleted = $quietChats->isEmpty() ? 0
            : static::query()->whereIn('order_id', $quietChats)->update(['deleted_at' => now(), 'deleted_by' => null]);

        $removed = static::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays((int) SiteSetting::number('chat.purge_days')))
            ->forceDelete();

        return [$deleted, (int) $removed];
    }

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
        return $query->whereHas('order.customer', fn ($q) => $q->whereColumn('customers.user_id', 'order_messages.sender_id'));
    }

    /** Messages the shop wrote (anyone other than the order's customer). */
    public function scopeFromStaff(Builder $query): Builder
    {
        return $query->whereDoesntHave('order.customer', fn ($q) => $q->whereColumn('customers.user_id', 'order_messages.sender_id'));
    }

    public function isFromStaff(Order $order): bool
    {
        return $this->sender_id === null || $this->sender_id !== $order->user_id;
    }
}

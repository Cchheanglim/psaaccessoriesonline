<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * A shopper (1:1 with users: a customer IS a user). Stores nothing but the link; the membership
 * tier and total spent are calculated from their orders.
 */
class Customer extends Model
{
    protected $fillable = ['user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true)->whereNull('archived_at');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Where the membership stands, worked out from the customer's orders:
     *  - spend: delivered orders since the membership last lapsed. A gap of more than
     *    LoyaltyTier::LAPSE_DAYS between orders (or since the last one) starts the count again.
     *  - lastOrderAt / expiresAt: the newest order (cancelled ones don't count) and 18 days after it.
     *
     * @return array{spend: float, lastOrderAt: mixed, expiresAt: mixed}
     */
    protected function membership(): Attribute
    {
        return Attribute::get(function () {
            $cancelled = OrderStatus::idFor('cancelled');
            $delivered = OrderStatus::idFor('delivered');
            $orders = $this->orders()->with(['items', 'currentStatus'])->orderBy('created_at')->orderBy('id')->get()
                ->reject(fn (Order $o) => $o->currentStatus?->order_status_id === $cancelled)->values();

            $spend = 0.0;
            $previous = null;
            foreach ($orders as $order) {
                if ($previous && $previous->created_at->diffInDays($order->created_at, true) > LoyaltyTier::LAPSE_DAYS) {
                    $spend = 0.0; // the membership had lapsed before this order
                }
                if ($order->currentStatus?->order_status_id === $delivered) {
                    $spend += (float) $order->total_usd;
                }
                $previous = $order;
            }
            $expiresAt = $previous?->created_at->copy()->addDays(LoyaltyTier::LAPSE_DAYS);
            if ($expiresAt && $expiresAt->isPast()) {
                $spend = 0.0;
            }

            return ['spend' => round($spend, 2), 'lastOrderAt' => $previous?->created_at, 'expiresAt' => $expiresAt];
        })->shouldCache();
    }

    /** The tier the current spending reaches (Plus, Pro or Max). */
    protected function tier(): Attribute
    {
        return Attribute::get(fn () => LoyaltyTier::forSpend($this->membership['spend']))->shouldCache();
    }

    /** What the customer has spent on delivered orders. */
    protected function totalSpentUsd(): Attribute
    {
        return Attribute::get(fn () => round(
            $this->orders()->inStatus('delivered')->with('items')->get()->sum(fn (Order $o) => $o->total_usd), 2
        ))->shouldCache();
    }
}

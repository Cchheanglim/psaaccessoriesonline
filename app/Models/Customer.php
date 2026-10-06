<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A shopper's profile (1:1 with users): loyalty points, tier, total spent, saved addresses and orders.
 * loyalty_points is a quick copy of the sum of loyalty_transactions; addPoints() keeps them in step.
 */
class Customer extends Model
{
    protected $fillable = ['user_id', 'loyalty_tier_id', 'loyalty_points', 'total_spent_usd'];

    protected $attributes = [
        'loyalty_points' => 0,
        'total_spent_usd' => 0,
    ];

    protected $casts = [
        'loyalty_points' => 'integer',
        'total_spent_usd' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tier()
    {
        return $this->belongsTo(LoyaltyTier::class, 'loyalty_tier_id');
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    /** Record a points change, then refresh the balance and the tier from the history. */
    public function addPoints(string $type, int $points, ?Order $order = null, ?User $by = null, ?string $note = null): void
    {
        if ($points === 0) {
            return;
        }
        $this->loyaltyTransactions()->create([
            'order_id' => $order?->id, 'handled_by' => $by?->id, 'type' => $type, 'points' => $points, 'note' => $note,
        ]);
        $balance = max(0, (int) $this->loyaltyTransactions()->sum('points'));
        $this->update(['loyalty_points' => $balance, 'loyalty_tier_id' => LoyaltyTier::forPoints($balance)->id]);
    }
}

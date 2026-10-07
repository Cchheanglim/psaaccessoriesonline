<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Plus, Pro, Max: how much a customer must spend to reach the tier and the discount it gives on
 * items at checkout. Membership lapses after LAPSE_DAYS without an order, and the spending
 * count starts again.
 */
class LoyaltyTier extends Model
{
    /** Days without an order before the membership drops back to the first tier. */
    public const LAPSE_DAYS = 18;

    protected $fillable = ['name', 'min_spend_usd', 'discount_percent'];

    protected $casts = [
        'min_spend_usd' => 'decimal:2',
        'discount_percent' => 'decimal:2',
    ];

    public static function lowest(): self
    {
        return static::orderBy('min_spend_usd')->firstOrFail();
    }

    /** The highest tier this much spending reaches. */
    public static function forSpend(float $spend): self
    {
        return static::where('min_spend_usd', '<=', max(0, $spend))->orderByDesc('min_spend_usd')->first() ?? static::lowest();
    }

    /** Dollars off these items for a member of this tier. */
    public function discountFor(float $subtotal): float
    {
        return round($subtotal * (float) $this->discount_percent / 100, 2);
    }
}

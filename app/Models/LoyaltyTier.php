<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Plus, Pro, Max: one row each in loyalty_tiers. A tier needs that much spending on delivered orders
 * (min_spend_usd) and gives discount_percent off the items at checkout. Plus (0) is everyone.
 * The membership lapses after membership.lapse_days (a site setting) without an order, and the
 * spending count starts again. Admins change the amounts on the Website page (Shop rules).
 */
class LoyaltyTier extends Model
{
    protected $fillable = ['name', 'min_spend_usd', 'discount_percent'];

    protected $casts = [
        'min_spend_usd' => 'float',
        'discount_percent' => 'float',
    ];

    /** @var Collection<int, self>|null lowest first, kept for the request */
    private static ?Collection $tiers = null;

    /** @return Collection<int, self> lowest first */
    public static function ordered(): Collection
    {
        return self::$tiers ??= static::query()->orderBy('min_spend_usd')->get();
    }

    public static function flushCache(): void
    {
        self::$tiers = null;
    }

    public static function lowest(): self
    {
        return self::ordered()->first();
    }

    public static function named(string $name): ?self
    {
        return self::ordered()->firstWhere('name', $name);
    }

    /** Days without an order before the membership drops back to Plus. */
    public static function lapseDays(): int
    {
        return max(1, (int) SiteSetting::number('membership.lapse_days'));
    }

    /** The highest tier this much spending reaches. */
    public static function forSpend(float $spend): self
    {
        return self::ordered()->filter(fn (self $t) => $spend >= $t->min_spend_usd)->last() ?? self::lowest();
    }

    /** The tier after this one, or null at the top. */
    public function next(): ?self
    {
        return self::ordered()->first(fn (self $t) => $t->min_spend_usd > $this->min_spend_usd);
    }

    /** Dollars off these items for a member of this tier. */
    public function discountFor(float $subtotal): float
    {
        return round($subtotal * $this->discount_percent / 100, 2);
    }
}

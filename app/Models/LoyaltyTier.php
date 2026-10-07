<?php

namespace App\Models;

/**
 * Plus, Pro, Max. Not a table: the amounts are site settings (membership.*) that admins change on the
 * Website page. Plus is everyone; Pro and Max need that much spending on delivered orders, and the
 * membership lapses after membership.lapse_days without an order (the spending count starts again).
 */
class LoyaltyTier
{
    public function __construct(
        public readonly string $name,
        public readonly float $min_spend_usd,
        public readonly float $discount_percent,
    ) {}

    /** @return list<self> lowest first */
    public static function all(): array
    {
        return [
            new self('Plus', 0, 0),
            new self('Pro', SiteSetting::number('membership.pro_min_spend_usd'), SiteSetting::number('membership.pro_discount_percent')),
            new self('Max', SiteSetting::number('membership.max_min_spend_usd'), SiteSetting::number('membership.max_discount_percent')),
        ];
    }

    public static function lowest(): self
    {
        return self::all()[0];
    }

    /** Days without an order before the membership drops back to Plus. */
    public static function lapseDays(): int
    {
        return max(1, (int) SiteSetting::number('membership.lapse_days'));
    }

    /** The highest tier this much spending reaches. */
    public static function forSpend(float $spend): self
    {
        $reached = self::lowest();
        foreach (self::all() as $tier) {
            if ($spend >= $tier->min_spend_usd) {
                $reached = $tier;
            }
        }

        return $reached;
    }

    /** The tier after this one, or null at the top. */
    public function next(): ?self
    {
        $tiers = self::all();
        foreach ($tiers as $i => $tier) {
            if ($tier->name === $this->name) {
                return $tiers[$i + 1] ?? null;
            }
        }

        return null;
    }

    /** Dollars off these items for a member of this tier. */
    public function discountFor(float $subtotal): float
    {
        return round($subtotal * $this->discount_percent / 100, 2);
    }
}

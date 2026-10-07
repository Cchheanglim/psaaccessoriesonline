<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A code customers type at checkout for money off their items (delivery is not discounted). */
class PromoCode extends Model
{
    protected $fillable = [
        'code', 'description', 'discount_type', 'discount_value', 'min_order_usd',
        'max_uses', 'max_uses_per_customer', 'starts_at', 'ends_at', 'is_active', 'show_to_customers',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_usd' => 'decimal:2',
        'max_uses' => 'integer',
        'max_uses_per_customer' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'show_to_customers' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public static function findByCode(?string $code): ?self
    {
        $code = self::normalize($code);

        return $code === '' ? null : static::where('code', $code)->first();
    }

    public static function normalize(?string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $code));
    }

    /** Orders that used it and weren't cancelled (a cancelled order gives the use back). */
    public function usedOrders()
    {
        return $this->orders()->whereDoesntHave('currentStatus', fn ($q) => $q->where('status', 'cancelled'));
    }

    /** 'scheduled', 'active', 'expired', 'used_up' or 'off', for the staff list. */
    public function state(?int $uses = null): string
    {
        $uses ??= $this->usedOrders()->count();

        return match (true) {
            ! $this->is_active => 'off',
            $this->ends_at && $this->ends_at->isPast() => 'expired',
            $this->max_uses !== null && $uses >= $this->max_uses => 'used_up',
            $this->starts_at && $this->starts_at->isFuture() => 'scheduled',
            default => 'active',
        };
    }

    /** Why this customer can't use it on an order of this size, or null when they can. */
    public function problemFor(?User $customer, float $subtotal): ?string
    {
        $state = $this->state();
        if ($state !== 'active') {
            return match ($state) {
                'scheduled' => "Code {$this->code} starts on ".$this->starts_at->format('j M Y').'.',
                'expired' => "Code {$this->code} has expired.",
                'used_up' => "Code {$this->code} has been fully used.",
                default => "Code {$this->code} isn't available.",
            };
        }
        if ($this->min_order_usd !== null && $subtotal < (float) $this->min_order_usd) {
            $more = number_format((float) $this->min_order_usd - $subtotal, 2);

            return "Code {$this->code} needs items worth \$".number_format((float) $this->min_order_usd, 2)." or more. Add \${$more} to use it.";
        }
        if ($customer && $this->max_uses_per_customer !== null
            && $this->usedOrders()->where('user_id', $customer->id)->count() >= $this->max_uses_per_customer) {
            return "You've already used code {$this->code}.";
        }

        return null;
    }

    /** Dollars off this subtotal, never more than the subtotal itself. */
    public function discountFor(float $subtotal): float
    {
        $off = $this->discount_type === 'percent'
            ? $subtotal * (float) $this->discount_value / 100
            : (float) $this->discount_value;

        return round(min($off, $subtotal), 2);
    }

    /** "10% off" / "$5.00 off" */
    public function label(): string
    {
        return $this->discount_type === 'percent'
            ? rtrim(rtrim(number_format((float) $this->discount_value, 2), '0'), '.').'% off'
            : '$'.number_format((float) $this->discount_value, 2).' off';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Bronze, Silver, Gold: how many points unlock the tier, its discount and how fast it earns. */
class LoyaltyTier extends Model
{
    protected $fillable = ['name', 'min_points', 'discount_percent', 'earn_multiplier'];

    protected $casts = [
        'min_points' => 'integer',
        'discount_percent' => 'decimal:2',
        'earn_multiplier' => 'decimal:2',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public static function lowest(): self
    {
        return static::orderBy('min_points')->firstOrFail();
    }

    /** The highest tier the points reach. */
    public static function forPoints(int $points): self
    {
        return static::where('min_points', '<=', max(0, $points))->orderByDesc('min_points')->first() ?? static::lowest();
    }
}

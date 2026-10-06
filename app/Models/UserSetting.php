<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One row per user (1:1): theme, language, currency and which notifications they want. */
class UserSetting extends Model
{
    public const CREATED_AT = null;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = ['user_id', 'theme', 'language', 'currency', 'notify_orders', 'notify_promotions', 'notify_price_drops'];

    protected $attributes = [
        'theme' => 'system',
        'language' => 'en',
        'currency' => 'USD',
        'notify_orders' => true,
        'notify_promotions' => true,
        'notify_price_drops' => true,
    ];

    protected $casts = [
        'notify_orders' => 'boolean',
        'notify_promotions' => 'boolean',
        'notify_price_drops' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

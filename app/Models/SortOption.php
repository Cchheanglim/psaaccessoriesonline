<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One choice in the shop's Sort By menu. Type 'sort' orders the catalog by sort_key;
 * type 'group' shows only its hand-picked products. A free-delivery group makes any
 * order containing one of its products ship free.
 */
class SortOption extends Model
{
    public const SORT_KEYS = [
        'featured' => 'Trending (shop order)',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'rating' => 'Top rated',
        'newest' => 'Newest first',
        'name' => 'Name: A to Z',
    ];

    protected $fillable = ['label', 'type', 'sort_key', 'free_delivery', 'is_active', 'sort_order'];

    protected $casts = [
        'free_delivery' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'sort_option_products');
    }

    public function scopeFreeDelivery($query)
    {
        return $query->where('type', 'group')->where('free_delivery', true)->where('is_active', true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One product picture; exactly one per product is the primary (shown on cards). */
class ProductImage extends Model
{
    protected $fillable = ['product_id', 'image_path', 'is_primary', 'sort_order'];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

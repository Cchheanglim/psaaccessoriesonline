<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Longer product information (1:1 with products). */
class ProductDetail extends Model
{
    protected $fillable = ['product_id', 'description', 'material', 'color', 'specifications'];

    protected $casts = ['specifications' => 'array'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

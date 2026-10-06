<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** The product's description (1:1 with products). Specifications are rows in product_specifications. */
class ProductDetail extends Model
{
    protected $fillable = ['product_id', 'description'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

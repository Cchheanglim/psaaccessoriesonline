<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One fact about a product (1NF: one value per row), e.g. Material = Stainless steel. */
class ProductSpecification extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'name', 'value', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

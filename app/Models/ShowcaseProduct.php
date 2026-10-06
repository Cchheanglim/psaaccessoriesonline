<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A product picked for the home page showcase. sort_order 0 is the big front card. */
class ShowcaseProduct extends Model
{
    public const MAX = 6;

    protected $fillable = ['product_id', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

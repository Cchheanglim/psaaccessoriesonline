<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A customer's rating (1-5) and comment on a product from one of their delivered orders. */
class ProductReview extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'customer_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'title',
        'title_khmer',
        'slug',
        'category',
        'category_label',
        'price_usd',
        'price_khr',
        'stock',
        'image',
        'badge',
        'rating',
        'review_count',
        'material',
        'color',
        'description',
        'status', // 'active', 'draft', 'archived'
        'gallery',
        'specifications',
    ];

    protected $casts = [
        'price_usd' => 'decimal:2',
        'price_khr' => 'integer',
        'rating' => 'decimal:1',
        'review_count' => 'integer',
        'stock' => 'integer',
        'gallery' => 'array',
        'specifications' => 'array',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCategory($query, $category)
    {
        if ($category && $category !== 'all') {
            return $query->where('category', $category);
        }
        return $query;
    }
}

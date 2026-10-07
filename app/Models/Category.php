<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A product category. Sub-categories (like "Jeans" under "Tees & Shirts") point to their parent. */
class Category extends Model
{
    protected $fillable = ['parent_id', 'name', 'slug', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /** The top-level category (itself when it has no parent). */
    public function root(): self
    {
        return $this->parent ?? $this;
    }
}

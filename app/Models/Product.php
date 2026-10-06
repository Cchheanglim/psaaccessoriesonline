<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A catalog item and its SELL price. Description and specs live in product_details (1:1),
 * pictures in product_images (1:N), stock and BUY cost in product_stocks (1:1).
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sku',
        'title',
        'title_khmer',
        'price_usd',
        'badge',
        'status', // 'active', 'draft', 'archived'
    ];

    protected $casts = [
        'price_usd' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function detail()
    {
        return $this->hasOne(ProductDetail::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function stock()
    {
        return $this->hasOne(ProductStock::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function showcase()
    {
        return $this->hasOne(ShowcaseProduct::class);
    }

    /** Hand-picked Sort By groups this product is in. */
    public function sortOptions()
    {
        return $this->belongsToMany(SortOption::class, 'sort_option_products');
    }

    /** Active free-delivery groups: an order with this product ships free. */
    public function freeDeliveryGroups()
    {
        return $this->sortOptions()
            ->where('sort_options.type', 'group')
            ->where('sort_options.free_delivery', true)
            ->where('sort_options.is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /** Find by SKU (what the storefront uses) or by "db-<id>" / numeric id. */
    public static function findByKey(string $key): ?self
    {
        $id = str_starts_with($key, 'db-') ? (int) substr($key, 3) : (ctype_digit($key) ? (int) $key : 0);

        return static::where('sku', $key)->when($id > 0, fn ($q) => $q->orWhere('id', $id))->first();
    }

    public function primaryImagePath(): ?string
    {
        $images = $this->relationLoaded('images') ? $this->images : $this->images()->get();

        return ($images->firstWhere('is_primary', true) ?? $images->first())?->image_path;
    }
}

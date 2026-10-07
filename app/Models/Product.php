<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A catalog item and its SELL price. Its description is in product_details (1:1; $product->description
 * reads and writes it), specs in product_specifications (1:N), pictures in product_images (1:N).
 * Stock is not stored: it is the sum of stock_movements (see Inventory).
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sku',
        'title',
        'title_khmer',
        'description', // saved in product_details
        'price_usd',
        'discount_percent', // a sale: percent off price_usd, null = no sale
        'discount_ends_at', // the sale stops after this moment (null = until staff remove it)
        'badge',
        'low_stock_threshold',
        'status', // 'active', 'draft', 'archived'
        'showcase_position', // place in the home showcase: 0 = the big front card, null = not in it
    ];

    /** How many products the home showcase holds. */
    public const SHOWCASE_MAX = 6;

    protected $casts = [
        'price_usd' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_ends_at' => 'datetime',
    ];

    /** The sale's percent off right now, or null when there is no running sale. */
    public function activeDiscountPercent(): ?float
    {
        $percent = (float) $this->discount_percent;
        if ($percent <= 0 || ($this->discount_ends_at && $this->discount_ends_at->isPast())) {
            return null;
        }

        return $percent;
    }

    /** What a customer pays for one now: the price less any running sale. */
    public function sellingPrice(): float
    {
        $percent = $this->activeDiscountPercent();

        return $percent === null ? (float) $this->price_usd : round((float) $this->price_usd * (100 - $percent) / 100, 2);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function detail()
    {
        return $this->hasOne(ProductDetail::class);
    }

    /** A description set on the product, saved to product_details after the product is saved. */
    private ?string $pendingDescription = null;

    private bool $descriptionChanged = false;

    protected static function booted(): void
    {
        static::saved(function (Product $product) {
            if ($product->descriptionChanged) {
                $product->detail()->updateOrCreate([], ['description' => $product->pendingDescription]);
                $product->descriptionChanged = false;
                $product->unsetRelation('detail');
            }
        });
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->descriptionChanged ? $this->pendingDescription : $this->detail?->description,
            set: function (?string $value) {
                $this->pendingDescription = $value;
                $this->descriptionChanged = true;

                return [];
            },
        );
    }


    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function specifications()
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Units on the shelf = SUM(stock_movements.quantity_change). Uses withSum() when the query loaded it. */
    protected function stockOnHand(): Attribute
    {
        return Attribute::get(function () {
            $sum = array_key_exists('stock_movements_sum_quantity_change', $this->attributes)
                ? $this->attributes['stock_movements_sum_quantity_change']
                : $this->stockMovements()->sum('quantity_change');

            return (int) $sum;
        });
    }

    /** Average BUY cost from received purchase orders (null until something was bought). */
    public function averageCost(): ?float
    {
        $row = $this->purchaseOrderItems()
            ->whereHas('purchaseOrder', fn ($q) => $q->where('status', 'received'))
            ->selectRaw('SUM(quantity * unit_cost_usd) AS cost, SUM(quantity) AS qty')
            ->first();

        return $row && (int) $row->qty > 0 ? round((float) $row->cost / (int) $row->qty, 2) : null;
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

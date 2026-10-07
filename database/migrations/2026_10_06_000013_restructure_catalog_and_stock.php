<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Database redesign, step 3 of 5 ("Products & Categories", "Stock & Suppliers").
 *
 * - categories table (with parent_id, so labels like "Jeans" become sub-categories of "Apparel")
 * - product_details (1:1), product_images (1:N, one primary), product_stocks (1:1)
 * - suppliers, purchase_orders, purchase_order_items: what the shop BUYS for
 * - stock_movements: every change to stock, with buy cost and sell price
 * Each product keeps its id, SKU, price and stock; its current stock is recorded as an
 * "Opening stock" movement so the history starts from today's numbers.
 */
return new class extends Migration
{
    // The names the staff product form already uses (resources/portal/product-form.html)
    private const CATEGORY_NAMES = [
        'apparel' => 'Tees & Shirts', 'watches' => 'Watches', 'bags' => 'Bags & Pouches', 'hair' => 'Hair & Clips',
        'charms' => 'Bag & Phone Charms', 'shoes' => 'Shoes', 'plush' => 'Plush & Crochet', 'socks' => 'Socks',
        'hats' => 'Caps & Hats', 'jewelry' => 'Jewelry', 'beauty' => 'Beauty', 'accessories' => 'Phone Accessories',
        'gifts' => 'Gift Sets', 'eyewear' => 'Shades & Eyewear',
    ];

    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('parent_id');
        });

        Schema::create('product_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete(); // 1:1
            $table->text('description')->nullable();
            $table->string('material')->nullable();
            $table->string('color')->nullable();
            $table->json('specifications')->nullable();
            $table->timestamps();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('image_path'); // a link (or, until pictures move to storage, an uploaded image)
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'sort_order']);
        });
        DB::statement('CREATE UNIQUE INDEX product_images_one_primary ON product_images (product_id) WHERE '.$this->isTrue('is_primary'));

        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete(); // 1:1
            $table->integer('quantity_on_hand')->default(0); // quick copy of SUM(stock_movements.quantity_change)
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->decimal('average_cost_usd', 8, 2)->nullable(); // BUY cost; unknown until the first purchase
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft'); // draft, ordered, received, cancelled
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->decimal('total_cost_usd', 10, 2)->default(0);
            $table->timestamps();
            $table->index('supplier_id');
            $table->index('ordered_by');
        });

        // Junction table: purchase_orders M:N products, with how much the shop BUYS each for
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost_usd', 8, 2);
            $table->unique(['purchase_order_id', 'product_id']);
            $table->index('product_id');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20); // purchase, sale, return, adjust, damage
            $table->integer('quantity_change'); // + into stock, - out of stock
            $table->decimal('unit_cost_usd', 8, 2)->nullable();  // BUY cost
            $table->decimal('unit_price_usd', 8, 2)->nullable(); // SELL price
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['product_id', 'created_at']);
            $table->index('handled_by');
            $table->index('order_item_id');
            $table->index('purchase_order_item_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->index('category_id');
        });

        $this->moveProducts();

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'slug', 'category', 'category_label', 'price_khr', 'stock', 'image', 'gallery',
                'specifications', 'rating', 'review_count', 'material', 'color', 'description',
            ]);
        });
    }

    private function moveProducts(): void
    {
        $now = now();
        $categoryIds = [];   // top-level slug => id
        $subcategoryIds = []; // "slug|label" => id

        foreach (DB::table('products')->orderBy('id')->get() as $p) {
            $slug = Str::slug((string) $p->category) ?: 'other';

            if (! isset($categoryIds[$slug])) {
                $categoryIds[$slug] = DB::table('categories')->insertGetId([
                    'name' => self::CATEGORY_NAMES[$slug] ?? Str::headline($slug), 'slug' => $slug,
                    'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $categoryId = $categoryIds[$slug];

            // A label that differs from the category's own name becomes a sub-category.
            $label = trim((string) $p->category_label);
            $topName = self::CATEGORY_NAMES[$slug] ?? Str::headline($slug);
            if ($label !== '' && strcasecmp($label, $topName) !== 0) {
                $key = $slug.'|'.mb_strtolower($label);
                if (! isset($subcategoryIds[$key])) {
                    $subSlug = $this->uniqueSlug($slug.'-'.Str::slug($label));
                    $subcategoryIds[$key] = DB::table('categories')->insertGetId([
                        'parent_id' => $categoryId, 'name' => $label, 'slug' => $subSlug,
                        'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
                $categoryId = $subcategoryIds[$key];
            }

            DB::table('products')->where('id', $p->id)->update(['category_id' => $categoryId]);

            $specs = $this->decode($p->specifications);
            DB::table('product_details')->insert([
                'product_id' => $p->id,
                'description' => $p->description,
                'material' => $p->material,
                'color' => $p->color,
                'specifications' => $specs ? json_encode($specs) : null,
                'created_at' => $p->created_at ?? $now, 'updated_at' => $now,
            ]);

            $gallery = array_values(array_filter((array) $this->decode($p->gallery), fn ($v) => is_string($v) && $v !== ''));
            if (! $gallery && $p->image) {
                $gallery = [$p->image];
            }
            foreach ($gallery as $i => $path) {
                DB::table('product_images')->insert([
                    'product_id' => $p->id, 'image_path' => $path, 'is_primary' => $i === 0, 'sort_order' => $i,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            $stock = max(0, (int) $p->stock);
            DB::table('product_stocks')->insert([
                'product_id' => $p->id, 'quantity_on_hand' => $stock, 'low_stock_threshold' => 5, 'average_cost_usd' => null, 'updated_at' => $now,
            ]);
            DB::table('stock_movements')->insert([
                'product_id' => $p->id, 'type' => 'adjust', 'quantity_change' => $stock, 'unit_price_usd' => $p->price_usd,
                'reason' => 'Opening stock (moved from the old products table)', 'created_at' => $now,
            ]);
        }
    }

    private function uniqueSlug(string $slug): string
    {
        $base = $slug;
        $n = 2;
        while (DB::table('categories')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    private function decode(mixed $value): mixed
    {
        if (is_array($value) || is_object($value)) {
            return (array) $value;
        }
        if (! is_string($value) || $value === '') {
            return null;
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function isTrue(string $column): string
    {
        return DB::getDriverName() === 'pgsql' ? $column : "{$column} = 1";
    }

    public function down(): void
    {
        throw new RuntimeException('The 2026-10 database redesign is one-way. Restore the database from a backup to go back.');
    }
};

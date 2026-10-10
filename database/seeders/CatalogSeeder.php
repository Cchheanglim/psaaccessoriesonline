<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Support\Inventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Loads the storefront catalog (database/data/catalog*.json) into products, categories,
 * product_images, product_specifications and stock movements.
 *
 * Safe to re-run: products are matched by SKU and keep their id and stock; ratings are never
 * seeded (they come from real customer reviews).
 */
class CatalogSeeder extends Seeder
{
    // Same names as the staff product form (resources/portal/product-form.html)
    private const CATEGORY_NAMES = [
        'apparel' => 'Tees & Shirts', 'watches' => 'Watches', 'bags' => 'Bags & Pouches', 'hair' => 'Hair & Clips',
        'charms' => 'Bag & Phone Charms', 'shoes' => 'Shoes', 'plush' => 'Plush & Crochet', 'socks' => 'Socks',
        'hats' => 'Caps & Hats', 'jewelry' => 'Jewelry', 'beauty' => 'Beauty', 'accessories' => 'Phone Accessories',
        'gifts' => 'Gift Sets', 'eyewear' => 'Shades & Eyewear',
    ];

    public function run(): void
    {
        // catalog.json = genz-01..12, catalog-photos.json = genz-13..32, catalog-more.json = genz-33..71 (images in /assets/images/products/)
        $catalog = [];
        foreach (['catalog.json', 'catalog-photos.json', 'catalog-more.json'] as $file) {
            $catalog = array_merge($catalog, json_decode(file_get_contents(database_path('data/'.$file)), true) ?? []);
        }

        DB::transaction(function () use ($catalog) {
            foreach ($catalog as $item) {
                // Additive: only create products that aren't in the catalog yet. An existing product is
                // left untouched so running this on every deploy never overwrites staff edits (title,
                // price, specs, images, stock). This fills a brand-new database; it doesn't reset a live one.
                if (Product::where('sku', $item['id'])->exists()) {
                    continue;
                }
                $product = Product::create([
                    'sku' => $item['id'],
                    'category_id' => $this->category($item['category'], $item['categoryLabel'] ?? null)->id,
                    'title' => $item['title'],
                    'title_khmer' => $item['titleKhmer'] ?? null,
                    'price_usd' => $item['priceUSD'],
                    'badge' => $item['badge'] ?? null,
                    'description' => $item['description'] ?? null,
                    'status' => $item['status'] ?? 'active',
                ]);

                // One row per fact (1NF)
                $i = 0;
                foreach ($item['specifications'] ?? [] as $name => $value) {
                    $product->specifications()->create(['name' => mb_substr($name, 0, 100), 'value' => (string) $value, 'sort_order' => $i++]);
                }

                $gallery = array_values(array_filter($item['gallery'] ?? [])) ?: array_values(array_filter([$item['image'] ?? null]));
                foreach ($gallery as $i => $path) {
                    $product->images()->create(['image_path' => $path, 'is_primary' => $i === 0, 'sort_order' => $i]);
                }

                Inventory::setQuantity($product, ($item['inStock'] ?? true) ? 50 : 0, null, 'Opening stock (catalog seed)');
            }

            // The home page showcase starts with these, unless staff already picked their own.
            if (! Product::whereNotNull('showcase_position')->exists()) {
                foreach (['genz-25', 'genz-24', 'genz-08'] as $i => $sku) {
                    Product::where('sku', $sku)->update(['showcase_position' => $i]);
                }
            }
        });
    }

    private function category(string $slug, ?string $label): Category
    {
        $slug = Str::slug($slug) ?: 'other';
        $top = Category::firstOrCreate(['slug' => $slug], ['name' => self::CATEGORY_NAMES[$slug] ?? Str::headline($slug)]);

        $label = trim((string) $label);
        if ($label === '' || strcasecmp($label, $top->name) === 0) {
            return $top;
        }

        return Category::firstOrCreate(
            ['slug' => $slug.'-'.Str::slug($label)],
            ['name' => $label, 'parent_id' => $top->id],
        );
    }
}

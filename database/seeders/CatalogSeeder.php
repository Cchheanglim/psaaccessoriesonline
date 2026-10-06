<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ShowcaseProduct;
use App\Support\Inventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Loads the storefront catalog (database/data/catalog*.json) into products, categories,
 * product_details, product_images and product_stocks.
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
                $product = Product::firstOrNew(['sku' => $item['id']]);
                $isNew = ! $product->exists;
                $product->fill([
                    'category_id' => $this->category($item['category'], $item['categoryLabel'] ?? null)->id,
                    'title' => $item['title'],
                    'title_khmer' => $item['titleKhmer'] ?? null,
                    'price_usd' => $item['priceUSD'],
                    'badge' => $item['badge'] ?? null,
                ]);
                if ($isNew) {
                    $product->status = $item['status'] ?? 'active';
                }
                $product->save();

                $product->detail()->updateOrCreate(['product_id' => $product->id], [
                    'description' => $item['description'] ?? null,
                    'specifications' => $item['specifications'] ?? [],
                ]);

                $gallery = array_values(array_filter($item['gallery'] ?? [])) ?: array_values(array_filter([$item['image'] ?? null]));
                $product->images()->delete();
                foreach ($gallery as $i => $path) {
                    $product->images()->create(['image_path' => $path, 'is_primary' => $i === 0, 'sort_order' => $i]);
                }

                if ($isNew) {
                    Inventory::setQuantity($product, ($item['inStock'] ?? true) ? 50 : 0, null, 'Opening stock (catalog seed)');
                } else {
                    Inventory::lock($product);
                }
            }

            // The home page showcase starts with these, unless staff already picked their own.
            if (! ShowcaseProduct::exists()) {
                $picks = ['genz-25', 'genz-24', 'genz-08'];
                $ids = Product::whereIn('sku', $picks)->pluck('id', 'sku');
                foreach (array_values(array_filter($picks, fn ($sku) => isset($ids[$sku]))) as $i => $sku) {
                    ShowcaseProduct::create(['product_id' => $ids[$sku], 'sort_order' => $i]);
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

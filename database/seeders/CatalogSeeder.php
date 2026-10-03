<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Loads the storefront catalog (database/data/catalog.json) into the products table.
 *
 * Safe to re-run: products are matched by SKU, and rows created by the older
 * ProductSeeder are matched by their slug so they keep their id and stock.
 */
class CatalogSeeder extends Seeder
{
    private const LEGACY_SLUGS = [];

    public function run(): void
    {
        // catalog.json = genz-01..12; catalog-photos.json = genz-13..32 (images in /assets/images/products/)
        $catalog = [];
        foreach (['catalog.json', 'catalog-photos.json'] as $file) {
            $catalog = array_merge($catalog, json_decode(file_get_contents(database_path('data/'.$file)), true) ?? []);
        }

        foreach ($catalog as $item) {
            $product = Product::where('sku', $item['id'])->first();

            if (! $product && isset(self::LEGACY_SLUGS[$item['id']])) {
                $product = Product::where('slug', self::LEGACY_SLUGS[$item['id']])->first();
            }

            $product ??= new Product(['stock' => ($item['inStock'] ?? true) ? 50 : 0, 'status' => $item['status'] ?? 'active']);

            $product->fill([
                'sku' => $item['id'],
                'title' => $item['title'],
                'title_khmer' => $item['titleKhmer'] ?? null,
                'slug' => Str::slug($item['title']),
                'category' => $item['category'],
                'category_label' => $item['categoryLabel'] ?? null,
                'price_usd' => $item['priceUSD'],
                'price_khr' => $item['priceKHR'],
                'image' => $item['image'] ?? null,
                'gallery' => $item['gallery'] ?? [],
                'badge' => $item['badge'] ?? null,
                // Ratings come only from real customer reviews, never from the catalog file.
                'rating' => round((float) ProductReview::where('product_sku', $item['id'])->avg('rating'), 1),
                'review_count' => ProductReview::where('product_sku', $item['id'])->count(),
                'description' => $item['description'] ?? null,
                'specifications' => $item['specifications'] ?? [],
            ])->save();
        }
    }
}

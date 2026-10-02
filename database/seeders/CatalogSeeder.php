<?php

namespace Database\Seeders;

use App\Models\Product;
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
    private const LEGACY_SLUGS = [
        'genz-01' => 'silver-chrome-star-pendant-necklace',
        'genz-02' => 'chunky-cyber-y2k-silver-ring-set-4pcs',
        'genz-03' => 'vintage-90s-tinted-oval-sunglasses',
        'genz-04' => 'futuristic-rimless-wrap-around-shades',
        'genz-05' => 'puffy-cloud-dumpling-nylon-shoulder-bag',
        'genz-06' => 'mini-boxy-y2k-metallic-crossbody-bag',
        'genz-07' => 'pastel-matte-french-hair-claw-clip-set-3pcs',
        'genz-09' => 'handmade-beaded-phone-charm-wristlet',
    ];

    public function run(): void
    {
        $catalog = json_decode(file_get_contents(database_path('data/catalog.json')), true);

        foreach ($catalog as $item) {
            $product = Product::where('sku', $item['id'])->first();

            if (! $product && isset(self::LEGACY_SLUGS[$item['id']])) {
                $product = Product::where('slug', self::LEGACY_SLUGS[$item['id']])->first();
            }

            $product ??= new Product(['stock' => $item['inStock'] ? 50 : 0, 'status' => 'active']);

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
                'rating' => $item['rating'] ?? 5,
                'review_count' => $item['reviewsCount'] ?? 0,
                'description' => $item['description'] ?? null,
                'specifications' => $item['specifications'] ?? [],
            ])->save();
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * The product list now lives in database/data/catalog.json and catalog-photos.json.
 * This seeder just delegates to CatalogSeeder so there is a single source of truth.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogSeeder::class);
    }
}

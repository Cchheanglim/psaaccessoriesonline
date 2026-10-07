<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The shop's "Sort By" menu, managed by staff instead of fixed in the page.
 *
 * sort_options: each choice in the menu, in sort_order. A choice either
 *   - sorts the whole catalog (type 'sort', sort_key = featured / price_asc / price_desc / rating / newest / name), or
 *   - shows a hand-picked group of products (type 'group', e.g. "New Drop", "Free Delivery").
 *   free_delivery on a group: an order with any of its products ships free.
 * sort_option_products: which products are in a group (sort_options N : M products).
 */
return new class extends Migration
{
    private const STARTING_OPTIONS = [
        ['label' => 'Trending Picks', 'sort_key' => 'featured'],
        ['label' => 'Price: Low to High', 'sort_key' => 'price_asc'],
        ['label' => 'Price: High to Low', 'sort_key' => 'price_desc'],
        ['label' => 'Top Rated', 'sort_key' => 'rating'],
    ];

    public function up(): void
    {
        Schema::create('sort_options', function (Blueprint $table) {
            $table->id();
            $table->string('label', 60);
            $table->string('type', 10)->default('sort'); // 'sort' or 'group'
            $table->string('sort_key', 20)->nullable();  // for type 'sort'
            $table->boolean('free_delivery')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sort_option_products', function (Blueprint $table) {
            $table->foreignId('sort_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['sort_option_id', 'product_id']);
            $table->index('product_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE sort_options ADD CONSTRAINT sort_options_type_check CHECK (type IN ('sort', 'group'))");
            DB::statement("ALTER TABLE sort_options ADD CONSTRAINT sort_options_key_check CHECK ((type = 'sort' AND sort_key IN ('featured', 'price_asc', 'price_desc', 'rating', 'newest', 'name')) OR (type = 'group' AND sort_key IS NULL))");
        }

        $now = now();
        foreach (self::STARTING_OPTIONS as $i => $option) {
            DB::table('sort_options')->insert($option + ['type' => 'sort', 'sort_order' => $i, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sort_option_products');
        Schema::dropIfExists('sort_options');
    }
};

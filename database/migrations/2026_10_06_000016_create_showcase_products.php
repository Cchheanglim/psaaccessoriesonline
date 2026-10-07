<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The floating product showcase at the top of the home page. Staff pick which products it shows
 * and in what order; the first one is the big front card.
 *
 * products 1 : 0..1 showcase_products (a product is on the showcase at most once)
 */
return new class extends Migration
{
    /** What the home page showed before it could be chosen. */
    private const STARTING_PICKS = ['genz-25', 'genz-24', 'genz-08'];

    public function up(): void
    {
        Schema::create('showcase_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0); // 0 = the front card
            $table->timestamps();
        });

        $now = now();
        $ids = DB::table('products')->whereIn('sku', self::STARTING_PICKS)->pluck('id', 'sku');
        foreach (array_values(array_filter(self::STARTING_PICKS, fn ($sku) => isset($ids[$sku]))) as $i => $sku) {
            DB::table('showcase_products')->insert(['product_id' => $ids[$sku], 'sort_order' => $i, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('showcase_products');
    }
};

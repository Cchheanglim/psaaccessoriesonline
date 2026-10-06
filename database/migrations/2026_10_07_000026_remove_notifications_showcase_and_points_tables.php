<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Three fewer tables:
 *  - user_notifications: the bell is built from orders, status history, payments and messages;
 *    "last opened" stays in the browser.
 *  - showcase_products: a product's place in the home showcase is products.showcase_position
 *    (empty = not in the showcase, 0 = the big front card).
 *  - loyalty_transactions (reward points): membership comes from spending, and points could not be
 *    spent on anything, so they are gone (with loyalty_tiers.earn_multiplier).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedTinyInteger('showcase_position')->nullable()->after('status');
        });
        foreach (DB::table('showcase_products')->orderBy('sort_order')->orderBy('id')->pluck('product_id')->values() as $i => $productId) {
            DB::table('products')->where('id', $productId)->update(['showcase_position' => $i]);
        }
        Schema::table('products', function (Blueprint $table) {
            $table->unique('showcase_position');
        });

        Schema::dropIfExists('showcase_products');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('loyalty_transactions');
        Schema::table('loyalty_tiers', function (Blueprint $table) {
            $table->dropColumn('earn_multiplier');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('One-way: restore the database backup to go back.');
    }
};

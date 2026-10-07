<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A sale on one product: staff set a percent off (and optionally the day it ends) when editing it.
 * The shop shows the old price crossed out; orders keep the price actually charged
 * (order_items.unit_price_usd), so ending a sale never changes old orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->nullable()->after('price_usd');
            $table->timestamp('discount_ends_at')->nullable()->after('discount_percent');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products ADD CONSTRAINT products_discount_percent_check CHECK (discount_percent IS NULL OR (discount_percent > 0 AND discount_percent < 100))');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_discount_percent_check');
        }
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'discount_ends_at']);
        });
    }
};

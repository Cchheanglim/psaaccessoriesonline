<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Store only what can't be worked out:
 *  - orders.discount_usd: the promo discount follows from the order's promo code and its items
 *    (a code's discount can't change once it has been used), so it is calculated (Order::promo_discount_usd).
 *  - orders.tax_usd: the shop charges no tax; it was always 0.
 *  - promo_codes.created_by: nothing uses who made a code.
 * Refuses to run if any of them hold something that would be lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        $lost = DB::table('orders')->whereNull('promo_code_id')->where('discount_usd', '!=', 0)->count()
            + DB::table('orders')->where('tax_usd', '!=', 0)->count();
        if ($lost > 0) {
            throw new RuntimeException("{$lost} order(s) have a discount without a promo code, or tax. Check them before dropping these columns.");
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_amounts_check');
        }
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_usd', 'tax_usd']);
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_amounts_check CHECK (member_discount_usd >= 0 AND delivery_fee_usd >= 0)');
        }

        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('One-way: restore the database backup to go back.');
    }
};

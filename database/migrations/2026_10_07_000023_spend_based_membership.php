<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membership is earned by spending, not points:
 *   Plus  from $0    no discount
 *   Pro   from $100  6% off items
 *   Max   from $500  17% off items
 * Spending counts delivered orders, and starts again after 18 days without an order
 * (LoyaltyTier::LAPSE_DAYS). Points still exist for rewards; they no longer decide the tier.
 *
 * Orders keep the member discount they got (orders.member_discount_usd) apart from the
 * promo code discount (orders.discount_usd), so receipts can show both.
 */
return new class extends Migration
{
    private const TIERS = [
        'Plus' => ['min_spend_usd' => 0, 'discount_percent' => 0],
        'Pro' => ['min_spend_usd' => 100, 'discount_percent' => 6],
        'Max' => ['min_spend_usd' => 500, 'discount_percent' => 17],
    ];

    public function up(): void
    {
        Schema::table('loyalty_tiers', function (Blueprint $table) {
            $table->decimal('min_spend_usd', 10, 2)->default(0)->after('name');
        });
        foreach (self::TIERS as $name => $values) {
            DB::table('loyalty_tiers')->where('name', $name)->update($values + ['updated_at' => now()]);
        }

        Schema::table('loyalty_tiers', function (Blueprint $table) {
            $table->dropUnique(['min_points']);
        });
        Schema::table('loyalty_tiers', function (Blueprint $table) {
            $table->dropColumn('min_points');
            $table->unique('min_spend_usd');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('member_discount_usd', 10, 2)->default(0)->after('discount_usd');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('One-way: restore the database backup to go back.');
    }
};

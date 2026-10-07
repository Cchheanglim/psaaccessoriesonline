<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Database redesign, step 5 of 5 (RECOMMEND page).
 *
 * - drops password_reset_tokens (there is no "forgot password" feature)
 * - indexes the foreign keys older migrations left without one (PostgreSQL does not add them)
 * - CHECK constraints so status / type / rating columns only accept allowed values
 *   (PostgreSQL only; SQLite, used by the tests, cannot add them to an existing table)
 */
return new class extends Migration
{
    private const CHECKS = [
        'users' => ['users_status_check' => "status IN ('Active', 'Suspended')"],
        'user_settings' => [
            'user_settings_theme_check' => "theme IN ('light', 'dark', 'system')",
            'user_settings_language_check' => "language IN ('en', 'km')",
            'user_settings_currency_check' => "currency IN ('USD', 'KHR')",
        ],
        'customers' => ['customers_points_check' => 'loyalty_points >= 0'],
        'customer_addresses' => ['customer_addresses_pin_check' => '(latitude IS NULL OR latitude BETWEEN -90 AND 90) AND (longitude IS NULL OR longitude BETWEEN -180 AND 180)'],
        'loyalty_transactions' => ['loyalty_transactions_type_check' => "type IN ('earn', 'redeem', 'adjust', 'expire')"],
        'payment_methods' => ['payment_methods_type_check' => "type IN ('khqr', 'bank', 'cod', 'other')"],
        'payments' => ['payments_status_check' => "status IN ('pending', 'slip_uploaded', 'paid_demo', 'verified', 'failed', 'refunded')"],
        'orders' => ['orders_amounts_check' => 'subtotal_usd >= 0 AND discount_usd >= 0 AND tax_usd >= 0 AND delivery_fee_usd >= 0 AND total_usd >= 0'],
        'order_items' => ['order_items_quantity_check' => 'quantity > 0'],
        'product_reviews' => ['product_reviews_rating_check' => 'rating BETWEEN 1 AND 5'],
        'product_stocks' => ['product_stocks_quantity_check' => 'quantity_on_hand >= 0'],
        'purchase_orders' => ['purchase_orders_status_check' => "status IN ('draft', 'ordered', 'received', 'cancelled')"],
        'purchase_order_items' => ['purchase_order_items_quantity_check' => 'quantity > 0 AND unit_cost_usd >= 0'],
        'stock_movements' => ['stock_movements_type_check' => "type IN ('purchase', 'sale', 'return', 'adjust', 'damage')"],
    ];

    public function up(): void
    {
        Schema::dropIfExists('password_reset_tokens');

        Schema::table('orders', fn (Blueprint $table) => $table->index('handled_by'));
        Schema::table('user_notifications', fn (Blueprint $table) => $table->index('order_id'));

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        foreach (self::CHECKS as $table => $checks) {
            foreach ($checks as $name => $condition) {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$condition})");
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('The 2026-10 database redesign is one-way. Restore the database from a backup to go back.');
    }
};

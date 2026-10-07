<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Promo codes: staff create a code (percent or dollar amount off, with optional minimum spend,
 * dates and use limits); customers type it at checkout. The order keeps which code it used
 * (orders.promo_code_id); a code's discount can't change once used, so what it took off is
 * calculated from the order's items (orders.discount_usd is dropped in 2026_10_07_000025).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // stored in capitals: SUMMER10
            $table->string('description', 160)->nullable();
            $table->string('discount_type', 10); // 'percent' or 'fixed'
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_order_usd', 10, 2)->nullable();
            $table->unsignedInteger('max_uses')->nullable(); // for everyone together; null = no limit
            $table->unsignedInteger('max_uses_per_customer')->nullable(); // null = no limit
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_to_customers')->default(false); // listed under "My coupons"
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->after('address_id')->constrained('promo_codes')->restrictOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE promo_codes ADD CONSTRAINT promo_codes_discount_type_check CHECK (discount_type IN ('percent', 'fixed'))");
            DB::statement('ALTER TABLE promo_codes ADD CONSTRAINT promo_codes_discount_value_check CHECK (discount_value > 0)');
        }

        $now = now();
        if (! DB::table('permissions')->where('name', 'manage_promotions')->exists()) {
            DB::table('permissions')->insert([
                'name' => 'manage_promotions', 'description' => 'Create, edit and turn off promo codes',
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promo_code_id');
        });
        Schema::dropIfExists('promo_codes');
        DB::table('permissions')->where('name', 'manage_promotions')->delete();
    }
};

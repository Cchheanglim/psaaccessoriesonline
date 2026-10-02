<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profile photos are saved as image text (often hundreds of KB), so they need a text column.
     * Other fields the forms accept up to 255 characters were only 191 wide
     * (Schema::defaultStringLength), which made longer values fail with a server error.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('avatar')->nullable()->change();
            $table->string('name', 255)->change();
            $table->string('email', 255)->change();
            $table->string('address', 255)->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('title', 255)->change();
            $table->string('title_khmer', 255)->nullable()->change();
            $table->string('slug', 255)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_name', 255)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_title', 255)->change();
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->string('account_name', 255)->nullable()->change();
            $table->string('account_number', 255)->nullable()->change();
            $table->string('description', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Widening is safe to keep; shrinking back could cut off saved photos and names.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fields the storefront pages in public/*.html rely on.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable()->unique()->after('id'); // storefront id, e.g. genz-01
            $table->json('gallery')->nullable();
            $table->json('specifications')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('Active'); // Active, Suspended
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('type')->default('other'); // khqr, bank, cod, other
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->text('payment_slip_url')->nullable()->change(); // holds an uploaded image
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->dropColumn(['sku', 'gallery', 'specifications']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};

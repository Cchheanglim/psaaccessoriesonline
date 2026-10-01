<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Products were created with a default 5.0 rating and seeded with invented
 * review counts, although no reviews feature exists. This clears those values
 * and removes the default, so a rating only appears once real reviews back it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('rating', 2, 1)->nullable()->default(null)->change();
        });

        DB::table('products')->update(['rating' => null, 'review_count' => 0]);
    }

    public function down(): void
    {
        DB::table('products')->whereNull('rating')->update(['rating' => 5.0]);

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('rating', 2, 1)->default(5.0)->change();
        });
    }
};

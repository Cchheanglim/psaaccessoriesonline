<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A banner picture at the top of the customer's account page. Like the profile photo,
     * it's stored as image text, so it needs a text column.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('banner')->nullable()->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('banner');
        });
    }
};

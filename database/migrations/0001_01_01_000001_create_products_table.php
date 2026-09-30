<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_khmer')->nullable();
            $table->string('slug')->unique();
            $table->string('category'); // jewelry, eyewear, bags, hair, charms
            $table->string('category_label')->nullable();
            $table->decimal('price_usd', 8, 2);
            $table->unsignedInteger('price_khr');
            $table->unsignedInteger('stock')->default(50);
            $table->text('image')->nullable();
            $table->string('badge')->nullable(); // Bestseller, Trending, Hot Drop
            $table->decimal('rating', 2, 1)->default(5.0);
            $table->unsignedInteger('review_count')->default(0);
            $table->string('material')->nullable();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'draft', 'archived'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

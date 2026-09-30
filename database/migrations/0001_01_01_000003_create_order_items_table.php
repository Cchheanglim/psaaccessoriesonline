<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('product_id')->nullable();
            $table->string('product_title');
            $table->text('product_image')->nullable();
            $table->decimal('price_usd', 8, 2);
            $table->unsignedInteger('price_khr');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('total_usd', 8, 2);
            $table->unsignedInteger('total_khr');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

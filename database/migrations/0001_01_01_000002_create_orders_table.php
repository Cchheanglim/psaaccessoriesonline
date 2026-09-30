<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('delivery_address');
            $table->text('delivery_notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('subtotal_usd', 10, 2);
            $table->unsignedInteger('subtotal_khr');
            $table->decimal('delivery_fee_usd', 8, 2)->default(1.50);
            $table->unsignedInteger('delivery_fee_khr')->default(6150);
            $table->decimal('total_usd', 10, 2);
            $table->unsignedInteger('total_khr');
            $table->string('payment_method')->default('bakong_khqr'); // bakong_khqr, aba_pay, cod
            $table->string('payment_status')->default('pending'); // pending, slip_uploaded, verified, failed
            $table->string('order_status')->default('pending_payment'); // pending_payment, processing, out_for_delivery, delivered, cancelled
            $table->string('payment_slip_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

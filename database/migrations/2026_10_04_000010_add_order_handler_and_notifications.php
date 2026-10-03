<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - orders.handled_by: the staff member who approved the order; the customer chats with them.
     * - order_messages.read_at: when the other side opened the thread (drives unread badges).
     * - user_notifications: the bell for customers (order updates, replies) and staff (new orders,
     *   slips, customer messages).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('handled_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('order_messages', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('body');
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('title', 200);
            $table->text('body')->nullable();
            $table->string('link', 300)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');

        Schema::table('order_messages', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handled_by');
        });
    }
};

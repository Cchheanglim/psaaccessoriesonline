<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deleting a message keeps its row and records when and by whom: order_messages.deleted_at and
 * deleted_by. The chat shows "Message deleted by ..." in its place. No new table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_messages', function (Blueprint $t) {
            $t->timestamp('deleted_at')->nullable();
            $t->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_messages', function (Blueprint $t) {
            $t->dropConstrainedForeignId('deleted_by');
            $t->dropColumn('deleted_at');
        });
    }
};

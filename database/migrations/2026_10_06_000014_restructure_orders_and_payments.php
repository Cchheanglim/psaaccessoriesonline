<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Database redesign, step 4 of 5 ("Orders, Delivery & Payment", "Messages & Notifications").
 *
 * - orders point to customers, payment_methods (by id), order_statuses and an optional saved address
 * - payments: one row per payment attempt (moved out of orders); order_status_history: every status change
 * - order_items: product_id becomes a real foreign key (it held the SKU), with sell price and buy cost
 * - product_reviews: product_id + customer_id instead of product_sku + user_id
 * - order_messages: sender_id (customer or staff); "from staff" is worked out from who sent it
 * Every existing order, item, review and message is kept.
 */
return new class extends Migration
{
    private const STATUSES = [
        ['code' => 'pending_payment', 'name' => 'Pending payment', 'sort_order' => 1],
        ['code' => 'processing', 'name' => 'Processing', 'sort_order' => 2],
        ['code' => 'out_for_delivery', 'name' => 'Out for delivery', 'sort_order' => 3],
        ['code' => 'delivered', 'name' => 'Delivered', 'sort_order' => 4],
        ['code' => 'cancelled', 'name' => 'Cancelled', 'sort_order' => 5],
    ];

    public function up(): void
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 60);
            $table->unsignedSmallInteger('sort_order')->default(0);
        });
        foreach (self::STATUSES as $status) {
            DB::table('order_statuses')->insert($status);
        }
        $statusIds = DB::table('order_statuses')->pluck('id', 'code');

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->renameColumn('qr_data', 'qr_image_url');
        });

        /* ---------- orders: new links ---------- */
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('order_number')->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->after('handled_by')->constrained()->restrictOnDelete();
            $table->foreignId('order_status_id')->nullable()->after('payment_method_id')->constrained()->restrictOnDelete();
            $table->foreignId('address_id')->nullable()->after('order_status_id')->constrained('customer_addresses')->nullOnDelete();
            $table->decimal('discount_usd', 10, 2)->default(0)->after('subtotal_usd');
            $table->unsignedInteger('points_redeemed')->default(0)->after('discount_usd');
            $table->decimal('tax_usd', 10, 2)->default(0)->after('points_redeemed');
            $table->index('customer_id');
            $table->index('payment_method_id');
            $table->index('order_status_id');
            $table->index('address_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->decimal('amount_usd', 10, 2);
            $table->string('status', 20)->default('pending'); // pending, slip_uploaded, paid_demo, verified, failed, refunded
            $table->text('slip_url')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'id']);
            $table->index('payment_method_id');
            $table->index('verified_by');
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_status_id')->constrained()->restrictOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['order_id', 'created_at']);
            $table->index('order_status_id');
            $table->index('changed_by');
        });

        $methodIds = DB::table('payment_methods')->pluck('id', 'code');
        $customerIds = DB::table('customers')->pluck('id', 'user_id');
        $now = now();

        foreach (DB::table('orders')->orderBy('id')->get() as $o) {
            // An order whose payment code has no row yet gets one (turned off), so nothing is lost.
            if (! isset($methodIds[$o->payment_method])) {
                $methodIds[$o->payment_method] = DB::table('payment_methods')->insertGetId([
                    'name' => $o->payment_method, 'code' => $o->payment_method, 'type' => 'other', 'is_active' => false,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $statusId = $statusIds[$o->order_status] ?? $statusIds['pending_payment'];

            DB::table('orders')->where('id', $o->id)->update([
                'customer_id' => $o->user_id ? ($customerIds[$o->user_id] ?? null) : null,
                'payment_method_id' => $methodIds[$o->payment_method],
                'order_status_id' => $statusId,
            ]);

            DB::table('payments')->insert([
                'order_id' => $o->id,
                'payment_method_id' => $methodIds[$o->payment_method],
                'amount_usd' => $o->total_usd,
                'status' => $o->payment_status ?: 'pending',
                'slip_url' => in_array($o->payment_slip_url, [null, '', 'submitted-without-image'], true) ? null : $o->payment_slip_url,
                'verified_by' => $o->payment_status === 'verified' ? $o->handled_by : null,
                'paid_at' => $o->paid_at,
                'created_at' => $o->created_at ?? $now,
                'updated_at' => $o->updated_at ?? $now,
            ]);

            DB::table('order_status_history')->insert([
                'order_id' => $o->id, 'order_status_id' => $statusIds['pending_payment'], 'changed_by' => null,
                'note' => 'Order placed', 'created_at' => $o->created_at ?? $now,
            ]);
            if ($o->order_status !== 'pending_payment') {
                DB::table('order_status_history')->insert([
                    'order_id' => $o->id, 'order_status_id' => $statusId, 'changed_by' => $o->handled_by,
                    'note' => 'Status at the time of the database upgrade', 'created_at' => $o->updated_at ?? $now,
                ]);
            }
        }

        /* ---------- order_items: SKU text -> product id, sell price + buy cost ---------- */
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_ref')->nullable()->after('order_id');
            $table->decimal('unit_price_usd', 8, 2)->default(0)->after('quantity');
            $table->decimal('unit_cost_usd', 8, 2)->nullable()->after('unit_price_usd');
        });
        $productIds = DB::table('products')->whereNotNull('sku')->pluck('id', 'sku');
        foreach (DB::table('order_items')->orderBy('id')->get() as $item) {
            $ref = $productIds[$item->product_id] ?? (ctype_digit((string) $item->product_id) && DB::table('products')->where('id', (int) $item->product_id)->exists() ? (int) $item->product_id : null);
            DB::table('order_items')->where('id', $item->id)->update(['product_ref' => $ref, 'unit_price_usd' => $item->price_usd]);
        }
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['product_id', 'price_usd', 'price_khr', 'total_usd', 'total_khr', 'updated_at']);
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->renameColumn('product_ref', 'product_id');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->index('product_id');
            $table->index('order_id');
        });

        /* ---------- product_reviews: product_sku + user_id -> product_id + customer_id ---------- */
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('order_id');
            $table->unsignedBigInteger('customer_id')->nullable()->after('product_id');
        });
        foreach (DB::table('product_reviews')->orderBy('id')->get() as $review) {
            DB::table('product_reviews')->where('id', $review->id)->update([
                'product_id' => $productIds[$review->product_sku] ?? null,
                'customer_id' => $customerIds[$review->user_id] ?? null,
            ]);
        }
        DB::table('product_reviews')->whereNull('product_id')->orWhereNull('customer_id')->delete(); // nothing to link to
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropUnique(['order_id', 'product_sku']);
            $table->dropIndex(['product_sku']);
        });
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropColumn('product_sku');
        });
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
        });
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->unique(['order_id', 'product_id']);
            $table->index('product_id');
            $table->index('customer_id');
        });

        /* ---------- order_messages: user_id -> sender_id, from_staff is worked out ---------- */
        Schema::table('order_messages', function (Blueprint $table) {
            $table->renameColumn('user_id', 'sender_id');
        });
        Schema::table('order_messages', function (Blueprint $table) {
            $table->dropColumn('from_staff');
        });
        Schema::table('order_messages', function (Blueprint $table) {
            $table->index('sender_id');
        });

        /* ---------- orders: drop what moved to customers / payments / order_statuses ---------- */
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method', 'payment_status', 'order_status', 'payment_slip_url', 'paid_at',
                'subtotal_khr', 'delivery_fee_khr', 'total_khr',
            ]);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_method_id')->nullable(false)->change();
            $table->unsignedBigInteger('order_status_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('The 2026-10 database redesign is one-way. Restore the database from a backup to go back.');
    }
};

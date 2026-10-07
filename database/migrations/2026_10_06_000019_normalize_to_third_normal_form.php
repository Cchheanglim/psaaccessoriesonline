<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Third normal form (docs/PsaOnline-database-3NF.drawio): every fact is stored once, and nothing that can
 * be calculated is stored. Data is moved first and checked; columns are only dropped afterwards.
 *
 * 1NF  product_details.specifications (a list in one cell) + material + color -> product_specifications rows
 * 2NF  order_items.product_title / product_image depend on the product only -> removed (products are archived, never deleted)
 * 3NF  removed because they can be calculated:
 *        customers.loyalty_points / loyalty_tier_id / total_spent_usd   (loyalty_transactions, loyalty_tiers, orders)
 *        product_stocks.quantity_on_hand / average_cost_usd             (stock_movements, purchase_order_items)
 *        orders.subtotal_usd / total_usd, purchase_orders.total_cost_usd (their items)
 *      removed because the fact already lives elsewhere:
 *        orders.order_status_id   -> newest order_status_history row
 *        orders.payment_method_id -> payments
 *        orders.handled_by        -> order_status_history.changed_by (first change after "placed")
 *        orders.points_redeemed   -> loyalty_transactions (type redeem)
 *        orders.customer_name / customer_phone / delivery_address / latitude / longitude -> the address (address_id)
 *        stock_movements.unit_cost_usd / unit_price_usd -> purchase_order_items / order_items
 *        user_settings.currency   -> the shop is USD only
 *      product_stocks keeps only low_stock_threshold -> moved to products, table dropped
 * New  customer_addresses.archived_at: an address used by an order is never edited (edit = new address).
 *
 * One-way: going back would need the removed copies rebuilt.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pg = DB::getDriverName() === 'pgsql';
        $now = now();

        /* ---------- 1NF: product specifications, one row per fact ---------- */
        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('value', 255);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unique(['product_id', 'name']);
        });
        foreach (DB::table('product_details')->orderBy('id')->get() as $d) {
            $rows = [];
            foreach ((json_decode((string) $d->specifications, true) ?: []) as $name => $value) {
                $name = trim((string) $name);
                if ($name !== '' && ! isset($rows[mb_strtolower($name)])) {
                    $rows[mb_strtolower($name)] = [$name, (string) $value];
                }
            }
            foreach (['Material' => $d->material, 'Color' => $d->color] as $name => $value) {
                if (trim((string) $value) !== '' && ! isset($rows[mb_strtolower($name)])) {
                    $rows[mb_strtolower($name)] = [$name, (string) $value];
                }
            }
            $i = 0;
            foreach ($rows as [$name, $value]) {
                DB::table('product_specifications')->insert(['product_id' => $d->product_id, 'name' => mb_substr($name, 0, 100), 'value' => mb_substr($value, 0, 255), 'sort_order' => $i++]);
            }
        }
        Schema::table('product_details', fn (Blueprint $t) => $t->dropColumn(['material', 'color', 'specifications']));

        /* ---------- stock: the movements are the stock ---------- */
        Schema::table('products', fn (Blueprint $t) => $t->unsignedInteger('low_stock_threshold')->default(5)->after('badge'));
        foreach (DB::table('product_stocks')->get() as $s) {
            DB::table('products')->where('id', $s->product_id)->update(['low_stock_threshold' => $s->low_stock_threshold ?? 5]);
            // The movements must add up to the stock that was on the shelf; carry over any difference.
            $sum = (int) DB::table('stock_movements')->where('product_id', $s->product_id)->sum('quantity_change');
            if ($sum !== (int) $s->quantity_on_hand) {
                DB::table('stock_movements')->insert([
                    'product_id' => $s->product_id, 'type' => 'adjust', 'quantity_change' => (int) $s->quantity_on_hand - $sum,
                    'reason' => 'Stock carried over (database normalized)', 'created_at' => $now,
                ]);
            }
        }
        if ($pg) {
            DB::statement('ALTER TABLE product_stocks DROP CONSTRAINT IF EXISTS product_stocks_quantity_check');
        }
        Schema::dropIfExists('product_stocks');

        Schema::table('stock_movements', fn (Blueprint $t) => $t->dropColumn(['unit_cost_usd', 'unit_price_usd']));
        Schema::table('purchase_orders', fn (Blueprint $t) => $t->dropColumn('total_cost_usd'));

        /* ---------- orders: point at the address; check the totals before dropping them ---------- */
        Schema::table('customer_addresses', fn (Blueprint $t) => $t->timestamp('archived_at')->nullable()->after('is_default'));

        $pending = DB::table('order_statuses')->where('code', 'pending_payment')->value('id');
        foreach (DB::table('orders')->orderBy('id')->get() as $o) {
            // 1. totals must equal what the items add up to, or dropping them would change history
            $subtotal = round((float) DB::table('order_items')->where('order_id', $o->id)->sum(DB::raw('quantity * unit_price_usd')), 2);
            $total = round($subtotal - (float) $o->discount_usd + (float) $o->tax_usd + (float) $o->delivery_fee_usd, 2);
            if (abs($subtotal - (float) $o->subtotal_usd) > 0.005 || abs($total - (float) $o->total_usd) > 0.005) {
                throw new RuntimeException("Order {$o->order_number}: stored totals ({$o->subtotal_usd} / {$o->total_usd}) differ from its items ({$subtotal} / {$total}). Nothing was changed.");
            }

            // 2. the address the order was sent to, as a (hidden) address of the customer
            if ($o->customer_id) {
                $match = DB::table('customer_addresses')->where('customer_id', $o->customer_id)->get()->first(fn ($a) => $a->recipient_name === $o->customer_name
                    && $a->phone === $o->customer_phone && $a->address_line === $o->delivery_address
                    && (float) $a->latitude === (float) $o->latitude && (float) $a->longitude === (float) $o->longitude);
                $addressId = $match?->id ?? DB::table('customer_addresses')->insertGetId([
                    'customer_id' => $o->customer_id, 'label' => 'Order address', 'recipient_name' => $o->customer_name,
                    'phone' => $o->customer_phone, 'address_line' => $o->delivery_address, 'latitude' => $o->latitude,
                    'longitude' => $o->longitude, 'is_default' => false, 'archived_at' => $now, 'created_at' => $o->created_at, 'updated_at' => $now,
                ]);
                DB::table('orders')->where('id', $o->id)->update(['address_id' => $addressId]);
            }

            // 3. the status: the newest history row must be the order's status
            $latest = DB::table('order_status_history')->where('order_id', $o->id)->orderByDesc('id')->first();
            if (! $latest || (int) $latest->order_status_id !== (int) $o->order_status_id) {
                DB::table('order_status_history')->insert([
                    'order_id' => $o->id, 'order_status_id' => $o->order_status_id ?? $pending, 'changed_by' => $o->handled_by,
                    'note' => 'Status carried over (database normalized)', 'created_at' => $latest ? $now : $o->created_at,
                ]);
            }

            // 4. who handles it = who made the first change after "placed"
            if ($o->handled_by) {
                $first = DB::table('order_status_history')->where('order_id', $o->id)->where('order_status_id', '!=', $pending)->orderBy('id')->first();
                if ($first && ! $first->changed_by) {
                    DB::table('order_status_history')->where('id', $first->id)->update(['changed_by' => $o->handled_by]);
                }
            }

            // 5. the payment method lives on the payments
            if (! DB::table('payments')->where('order_id', $o->id)->exists()) {
                DB::table('payments')->insert([
                    'order_id' => $o->id, 'payment_method_id' => $o->payment_method_id, 'amount_usd' => $total, 'status' => 'pending',
                    'created_at' => $o->created_at, 'updated_at' => $now,
                ]);
            }

            // 6. points spent on the order live in loyalty_transactions
            if ((int) $o->points_redeemed > 0 && $o->customer_id && ! DB::table('loyalty_transactions')->where('order_id', $o->id)->where('type', 'redeem')->exists()) {
                DB::table('loyalty_transactions')->insert([
                    'customer_id' => $o->customer_id, 'order_id' => $o->id, 'type' => 'redeem', 'points' => -(int) $o->points_redeemed,
                    'note' => "Points used on order {$o->order_number}", 'created_at' => $o->created_at,
                ]);
            }
        }

        if ($pg) {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_amounts_check');
        }
        Schema::table('orders', function (Blueprint $t) {
            $t->dropIndex(['handled_by']);
            $t->dropForeign(['handled_by']);
            $t->dropForeign(['payment_method_id']);
            $t->dropForeign(['order_status_id']);
            $t->dropIndex(['payment_method_id']);
            $t->dropIndex(['order_status_id']);
        });
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn([
            'handled_by', 'payment_method_id', 'order_status_id', 'customer_name', 'customer_phone', 'delivery_address',
            'latitude', 'longitude', 'subtotal_usd', 'total_usd', 'points_redeemed',
        ]));
        if ($pg) {
            DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_amounts_check CHECK (discount_usd >= 0 AND tax_usd >= 0 AND delivery_fee_usd >= 0)');
        }
        // Every order belongs to a customer and ships to an address (both now required, never cleared).
        if (DB::table('orders')->whereNull('customer_id')->orWhereNull('address_id')->exists()) {
            throw new RuntimeException('Some orders have no customer or address; they need one before this migration can run.');
        }
        Schema::table('orders', function (Blueprint $t) {
            $t->dropForeign(['customer_id']);
            $t->dropForeign(['address_id']);
        });
        Schema::table('orders', function (Blueprint $t) {
            $t->unsignedBigInteger('customer_id')->nullable(false)->change();
            $t->unsignedBigInteger('address_id')->nullable(false)->change();
            $t->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $t->foreign('address_id')->references('id')->on('customer_addresses')->restrictOnDelete();
        });

        /* ---------- order_items: the product gives the name and photo ---------- */
        Schema::table('order_items', function (Blueprint $t) {
            $t->dropForeign(['product_id']);
        });
        Schema::table('order_items', function (Blueprint $t) {
            $t->dropColumn(['product_title', 'product_image']);
            $t->unsignedBigInteger('product_id')->nullable(false)->change();
            $t->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $t->unique(['order_id', 'product_id']);
        });

        /* ---------- customers: points, tier and total spent are calculated ---------- */
        foreach (DB::table('customers')->get() as $c) {
            // The history must add up to the balance customers saw; carry over any difference.
            $sum = (int) DB::table('loyalty_transactions')->where('customer_id', $c->id)->sum('points');
            if ($sum !== (int) $c->loyalty_points) {
                DB::table('loyalty_transactions')->insert([
                    'customer_id' => $c->id, 'type' => 'adjust', 'points' => (int) $c->loyalty_points - $sum,
                    'note' => 'Balance carried over (database normalized)', 'created_at' => $now,
                ]);
            }
        }
        if ($pg) {
            DB::statement('ALTER TABLE customers DROP CONSTRAINT IF EXISTS customers_points_check');
        }
        Schema::table('customers', function (Blueprint $t) {
            $t->dropForeign(['loyalty_tier_id']);
            $t->dropIndex(['loyalty_tier_id']);
        });
        Schema::table('customers', fn (Blueprint $t) => $t->dropColumn(['loyalty_tier_id', 'loyalty_points', 'total_spent_usd']));
        Schema::table('loyalty_tiers', fn (Blueprint $t) => $t->unique('min_points'));

        /* ---------- user_settings: USD only ---------- */
        if ($pg) {
            DB::statement('ALTER TABLE user_settings DROP CONSTRAINT IF EXISTS user_settings_currency_check');
        }
        Schema::table('user_settings', fn (Blueprint $t) => $t->dropColumn('currency'));
    }

    public function down(): void
    {
        throw new RuntimeException('This migration is one-way: restore the database backup to go back.');
    }
};

<?php

use App\Models\OrderStatus;
use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The database the team presents (docs/Final Draft.drawio, 28 tables). Five tables come back and
 * every value moves into them; nothing is lost:
 *  - permissions      role_permissions.permission (a name)  -> role_permissions.permission_id
 *  - order_statuses   order_status_history.status (a code)  -> order_status_history.order_status_id
 *  - customers        orders, customer_addresses, product_reviews: user_id -> customer_id
 *  - product_details  products.description                  -> product_details.description
 *  - loyalty_tiers    site_settings membership.pro_* / max_* -> one row per tier (Plus / Pro / Max)
 * site_settings also records who changed a row last (updated_by).
 */
return new class extends Migration
{
    public function up(): void
    {
        $pg = DB::getDriverName() === 'pgsql';
        $now = now();

        /* ---------- permissions ---------- */
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name', 80)->unique(); // e.g. manage_stock
            $t->string('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        foreach (Permission::LIST as $name => [, , $what]) {
            DB::table('permissions')->insert(['name' => $name, 'description' => $what, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
        $permissionId = DB::table('permissions')->pluck('id', 'name');
        $links = DB::table('role_permissions')->get(['role_id', 'permission']);
        Schema::drop('role_permissions');
        Schema::create('role_permissions', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $t->primary(['role_id', 'permission_id']);
        });
        $rows = $links->filter(fn ($l) => isset($permissionId[$l->permission]))
            ->map(fn ($l) => ['role_id' => $l->role_id, 'permission_id' => $permissionId[$l->permission]])
            ->unique(fn ($r) => $r['role_id'].'|'.$r['permission_id'])->values()->all();
        if ($rows) {
            DB::table('role_permissions')->insert($rows);
        }

        /* ---------- order_statuses ---------- */
        Schema::create('order_statuses', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name', 60);
            $t->integer('sort_order')->default(0);
        });
        $i = 0;
        foreach (OrderStatus::NAMES as $code => $name) {
            DB::table('order_statuses')->insert(['code' => $code, 'name' => $name, 'sort_order' => ++$i]);
        }
        $statusId = DB::table('order_statuses')->pluck('id', 'code');
        Schema::table('order_status_history', fn (Blueprint $t) => $t->foreignId('order_status_id')->nullable()->after('order_id')->constrained('order_statuses'));
        foreach ($statusId as $code => $id) {
            DB::table('order_status_history')->where('status', $code)->update(['order_status_id' => $id]);
        }
        if ($pg) {
            DB::statement('ALTER TABLE order_status_history DROP CONSTRAINT IF EXISTS order_status_history_status_check');
        }
        Schema::table('order_status_history', fn (Blueprint $t) => $t->dropIndex(['order_id', 'status']));
        Schema::table('order_status_history', fn (Blueprint $t) => $t->dropColumn('status'));
        Schema::table('order_status_history', function (Blueprint $t) {
            $t->unsignedBigInteger('order_status_id')->nullable(false)->change();
            $t->index(['order_id', 'order_status_id']);
        });

        /* ---------- customers ---------- */
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->timestamps();
        });
        // Every shopper: buyer accounts and anyone who has an order, address or review.
        $buyerRole = DB::table('roles')->where('name', 'buyer')->value('id');
        $userIds = collect()
            ->merge(DB::table('users')->where('role_id', $buyerRole)->pluck('id'))
            ->merge(DB::table('orders')->pluck('user_id'))
            ->merge(DB::table('customer_addresses')->whereNotNull('user_id')->pluck('user_id'))
            ->merge(DB::table('product_reviews')->pluck('user_id'))
            ->unique()->sort()->values();
        foreach ($userIds as $userId) {
            DB::table('customers')->insert(['user_id' => $userId, 'created_at' => $now, 'updated_at' => $now]);
        }
        $customerOf = DB::table('customers')->pluck('id', 'user_id');

        // customer_addresses
        DB::statement('DROP INDEX IF EXISTS customer_addresses_one_default');
        Schema::table('customer_addresses', fn (Blueprint $t) => $t->foreignId('customer_id')->nullable()->after('id')->constrained()->cascadeOnDelete());
        foreach ($customerOf as $userId => $customerId) {
            DB::table('customer_addresses')->where('user_id', $userId)->update(['customer_id' => $customerId]);
        }
        Schema::table('customer_addresses', function (Blueprint $t) {
            $t->dropForeign(['user_id']);
            $t->dropIndex(['user_id']);
        });
        Schema::table('customer_addresses', fn (Blueprint $t) => $t->dropColumn('user_id'));
        Schema::table('customer_addresses', fn (Blueprint $t) => $t->index('customer_id'));
        DB::statement('CREATE UNIQUE INDEX customer_addresses_one_default ON customer_addresses (customer_id) WHERE is_default = '.($pg ? 'true' : '1'));

        // orders (every order belongs to a customer, and a customer with orders is never deleted)
        Schema::table('orders', fn (Blueprint $t) => $t->unsignedBigInteger('customer_id')->nullable()->after('order_number'));
        foreach ($customerOf as $userId => $customerId) {
            DB::table('orders')->where('user_id', $userId)->update(['customer_id' => $customerId]);
        }
        Schema::table('orders', function (Blueprint $t) {
            $t->dropForeign(['user_id']);
            $t->dropIndex(['user_id']);
        });
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('user_id'));
        Schema::table('orders', function (Blueprint $t) {
            $t->unsignedBigInteger('customer_id')->nullable(false)->change();
            $t->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $t->index('customer_id');
        });

        // product_reviews
        Schema::table('product_reviews', fn (Blueprint $t) => $t->unsignedBigInteger('customer_id')->nullable()->after('order_id'));
        foreach ($customerOf as $userId => $customerId) {
            DB::table('product_reviews')->where('user_id', $userId)->update(['customer_id' => $customerId]);
        }
        Schema::table('product_reviews', function (Blueprint $t) {
            $t->dropForeign(['user_id']);
            $t->dropIndex(['user_id']);
        });
        Schema::table('product_reviews', fn (Blueprint $t) => $t->dropColumn('user_id'));
        Schema::table('product_reviews', function (Blueprint $t) {
            $t->unsignedBigInteger('customer_id')->nullable(false)->change();
            $t->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $t->index('customer_id');
        });

        /* ---------- product_details ---------- */
        Schema::create('product_details', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $t->text('description')->nullable();
            $t->timestamps();
        });
        foreach (DB::table('products')->whereNotNull('description')->get(['id', 'description']) as $p) {
            DB::table('product_details')->insert(['product_id' => $p->id, 'description' => $p->description, 'created_at' => $now, 'updated_at' => $now]);
        }
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('description'));

        /* ---------- loyalty_tiers ---------- */
        Schema::create('loyalty_tiers', function (Blueprint $t) {
            $t->id();
            $t->string('name', 30)->unique(); // Plus / Pro / Max
            $t->decimal('min_spend_usd', 10, 2)->unique();
            $t->decimal('discount_percent', 5, 2)->default(0);
            $t->timestamps();
        });
        $setting = fn (string $key, string $default) => DB::table('site_settings')->where('setting_key', $key)->value('setting_value') ?? $default;
        DB::table('loyalty_tiers')->insert([
            ['name' => 'Plus', 'min_spend_usd' => 0, 'discount_percent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pro', 'min_spend_usd' => $setting('membership.pro_min_spend_usd', '100'), 'discount_percent' => $setting('membership.pro_discount_percent', '6'), 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Max', 'min_spend_usd' => $setting('membership.max_min_spend_usd', '500'), 'discount_percent' => $setting('membership.max_discount_percent', '17'), 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('site_settings')->whereIn('setting_key', ['membership.pro_min_spend_usd', 'membership.pro_discount_percent', 'membership.max_min_spend_usd', 'membership.max_discount_percent'])->delete();

        /* ---------- site_settings: who changed it ---------- */
        Schema::table('site_settings', fn (Blueprint $t) => $t->foreignId('updated_by')->nullable()->after('setting_value')->constrained('users')->nullOnDelete());
    }

    public function down(): void
    {
        throw new RuntimeException('One-way: restore the database backup to go back.');
    }
};

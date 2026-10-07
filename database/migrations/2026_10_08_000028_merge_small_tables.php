<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Four small tables are merged into the tables that use them. No data is lost: every value moves.
 *  - product_details (only a description)  -> products.description
 *  - order_statuses (5 fixed rows)         -> order_status_history.status (the code, checked)
 *  - permissions (12 fixed names)          -> role_permissions.permission (the name; the list is in the code)
 *  - customers (only user_id)              -> orders, customer_addresses and product_reviews point at users
 */
return new class extends Migration
{
    private const STATUSES = ['pending_payment', 'processing', 'out_for_delivery', 'delivered', 'cancelled'];

    public function up(): void
    {
        $pg = DB::getDriverName() === 'pgsql';

        /* ---------- product_details -> products.description ---------- */
        Schema::table('products', fn (Blueprint $t) => $t->text('description')->nullable()->after('title_khmer'));
        foreach (DB::table('product_details')->whereNotNull('description')->get(['product_id', 'description']) as $d) {
            DB::table('products')->where('id', $d->product_id)->update(['description' => $d->description]);
        }
        Schema::drop('product_details');

        /* ---------- order_statuses -> order_status_history.status ---------- */
        Schema::table('order_status_history', fn (Blueprint $t) => $t->string('status', 30)->default('pending_payment')->after('order_id'));
        foreach (DB::table('order_statuses')->get(['id', 'code']) as $s) {
            DB::table('order_status_history')->where('order_status_id', $s->id)->update(['status' => $s->code]);
        }
        Schema::table('order_status_history', function (Blueprint $t) {
            $t->dropForeign(['order_status_id']);
            $t->dropIndex(['order_status_id']);
        });
        Schema::table('order_status_history', function (Blueprint $t) {
            $t->dropColumn('order_status_id');
            $t->index(['order_id', 'status']);
        });
        if ($pg) {
            DB::statement("ALTER TABLE order_status_history ADD CONSTRAINT order_status_history_status_check CHECK (status IN ('".implode("', '", self::STATUSES)."'))");
        }
        Schema::drop('order_statuses');

        /* ---------- permissions -> role_permissions.permission ---------- */
        $links = DB::table('role_permissions')->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->get(['role_permissions.role_id', 'permissions.name']);
        Schema::drop('role_permissions');
        Schema::create('role_permissions', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->string('permission', 80); // e.g. manage_stock (App\Models\Permission lists them)
            $t->primary(['role_id', 'permission']);
        });
        DB::table('role_permissions')->insert($links->map(fn ($l) => ['role_id' => $l->role_id, 'permission' => $l->name])->unique(fn ($r) => $r['role_id'].'|'.$r['permission'])->values()->all());
        Schema::drop('permissions');

        /* ---------- customers -> users ---------- */
        $userOf = DB::table('customers')->pluck('user_id', 'id');

        // customer_addresses
        DB::statement('DROP INDEX IF EXISTS customer_addresses_one_default');
        Schema::table('customer_addresses', fn (Blueprint $t) => $t->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete());
        foreach ($userOf as $customerId => $userId) {
            DB::table('customer_addresses')->where('customer_id', $customerId)->update(['user_id' => $userId]);
        }
        Schema::table('customer_addresses', function (Blueprint $t) {
            $t->dropForeign(['customer_id']);
            $t->dropIndex(['customer_id']);
        });
        Schema::table('customer_addresses', function (Blueprint $t) {
            $t->dropColumn('customer_id');
            $t->index('user_id');
        });
        DB::statement('CREATE UNIQUE INDEX customer_addresses_one_default ON customer_addresses (user_id) WHERE is_default = '.($pg ? 'true' : '1'));

        // orders (every order belongs to a customer: required, never deleted with the user)
        Schema::table('orders', fn (Blueprint $t) => $t->unsignedBigInteger('user_id')->nullable()->after('order_number'));
        foreach ($userOf as $customerId => $userId) {
            DB::table('orders')->where('customer_id', $customerId)->update(['user_id' => $userId]);
        }
        if (DB::table('orders')->whereNull('user_id')->exists()) {
            throw new RuntimeException('Some orders have no customer, so they cannot be linked to a user.');
        }
        Schema::table('orders', function (Blueprint $t) {
            $t->dropForeign(['customer_id']);
            $t->dropIndex(['customer_id']);
        });
        Schema::table('orders', function (Blueprint $t) {
            $t->dropColumn('customer_id');
        });
        Schema::table('orders', function (Blueprint $t) {
            $t->unsignedBigInteger('user_id')->nullable(false)->change();
            $t->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $t->index('user_id');
        });

        // product_reviews
        Schema::table('product_reviews', fn (Blueprint $t) => $t->unsignedBigInteger('user_id')->nullable()->after('order_id'));
        foreach ($userOf as $customerId => $userId) {
            DB::table('product_reviews')->where('customer_id', $customerId)->update(['user_id' => $userId]);
        }
        Schema::table('product_reviews', function (Blueprint $t) {
            $t->dropForeign(['customer_id']);
            $t->dropIndex(['customer_id']);
        });
        Schema::table('product_reviews', function (Blueprint $t) {
            $t->dropColumn('customer_id');
        });
        Schema::table('product_reviews', function (Blueprint $t) {
            $t->unsignedBigInteger('user_id')->nullable(false)->change();
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->index('user_id');
        });

        Schema::drop('customers');
    }

    public function down(): void
    {
        throw new RuntimeException('One-way: restore the database backup to go back.');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Database redesign, step 1 of 5 (docs/PsaOnline-database-final.drawio, "Users, Roles & Permissions").
 *
 * Roles become their own table (users.role_id), permissions are linked to roles through
 * role_permissions (many-to-many), and the unused / renamed user columns are cleaned up.
 * Existing users keep their role.
 */
return new class extends Migration
{
    private const ROLES = [
        'buyer' => 'Customer account: shops, orders, reviews',
        'staff' => 'Runs orders, payments, products and stock',
        'admin' => 'Full access, including users and payment methods',
    ];

    private const PERMISSIONS = [
        'manage_products' => 'Add and edit products and categories',
        'manage_stock' => 'Adjust stock and receive purchase orders',
        'manage_suppliers' => 'Add suppliers and create purchase orders',
        'manage_orders' => 'Dispatch and deliver orders',
        'verify_payments' => 'Approve or reject payment slips',
        'cancel_orders' => 'Cancel orders and return their stock',
        'manage_messages' => 'Reply to customers about their orders',
        'adjust_loyalty' => 'Add or remove loyalty points by hand',
        'view_reports' => 'Open the sales, profit and stock reports',
        'manage_users' => 'Create staff accounts, change roles, suspend accounts',
        'manage_payment_methods' => 'Add, edit and turn off payment methods',
        'delete_products' => 'Remove products from the catalog',
    ];

    private const STAFF_PERMISSIONS = [
        'manage_products', 'manage_stock', 'manage_suppliers', 'manage_orders',
        'verify_payments', 'manage_messages', 'view_reports',
    ];

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Junction table: a role has many permissions and a permission belongs to many roles.
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
            $table->index('permission_id');
        });

        $now = now();
        foreach (self::ROLES as $name => $description) {
            DB::table('roles')->insert(['name' => $name, 'description' => $description, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach (self::PERMISSIONS as $name => $description) {
            DB::table('permissions')->insert(['name' => $name, 'description' => $description, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
        $roleIds = DB::table('roles')->pluck('id', 'name');
        $permissionIds = DB::table('permissions')->pluck('id', 'name');
        foreach ($permissionIds as $name => $permissionId) {
            DB::table('role_permissions')->insert(['role_id' => $roleIds['admin'], 'permission_id' => $permissionId]);
            if (in_array($name, self::STAFF_PERMISSIONS, true)) {
                DB::table('role_permissions')->insert(['role_id' => $roleIds['staff'], 'permission_id' => $permissionId]);
            }
        }

        // users.role (text) -> users.role_id (foreign key)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->index('role_id');
        });
        foreach ($roleIds as $name => $roleId) {
            DB::table('users')->where('role', $name)->update(['role_id' => $roleId]);
        }
        DB::table('users')->whereNull('role_id')->update(['role_id' => $roleIds['buyer']]);
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
        });

        // SQLite cannot drop a column that a CHECK constraint (the old enum) still mentions,
        // so turn it into a plain column first. PostgreSQL drops the CHECK with the column.
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', fn (Blueprint $table) => $table->string('role')->nullable()->default(null)->change());
        }

        // Login by phone needs phone numbers to be unique; empty text becomes "no phone".
        DB::table('users')->where('phone', '')->update(['phone' => null]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'email_verified_at']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('avatar', 'avatar_url');
            $table->renameColumn('banner', 'banner_url');
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('The 2026-10 database redesign is one-way. Restore the database from a backup to go back.');
    }
};

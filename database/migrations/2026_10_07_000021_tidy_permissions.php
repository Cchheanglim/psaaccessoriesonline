<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permissions are now edited by admins (Staff & RBAC > Roles & permissions) and checked by the server
 * for every staff action. This removes the one permission no feature uses (adjust_loyalty) and makes
 * each description say exactly what it allows.
 */
return new class extends Migration
{
    private const DESCRIPTIONS = [
        'manage_products' => 'Add and edit products and categories, the home showcase, the Sort By menu, the headline and social links',
        'manage_stock' => 'Change stock counts, and create and receive purchase orders',
        'manage_suppliers' => 'Add and edit suppliers',
        'manage_orders' => 'Dispatch and deliver orders',
        'verify_payments' => 'Approve or reject payment slips',
        'cancel_orders' => 'Cancel orders (their stock goes back)',
        'manage_messages' => 'Read the messages inbox and reply to customers',
        'view_reports' => 'Open the sales and stock reports',
        'manage_users' => 'Create accounts, change roles, suspend accounts',
        'manage_payment_methods' => 'Add, edit, turn off and delete payment methods',
        'delete_products' => 'Delete products, categories, Sort By options and suppliers',
    ];

    public function up(): void
    {
        $unused = DB::table('permissions')->where('name', 'adjust_loyalty')->value('id');
        if ($unused) {
            DB::table('role_permissions')->where('permission_id', $unused)->delete();
            DB::table('permissions')->where('id', $unused)->delete();
        }
        foreach (self::DESCRIPTIONS as $name => $description) {
            DB::table('permissions')->where('name', $name)->update(['description' => $description, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('One-way: restore the database backup to go back.');
    }
};

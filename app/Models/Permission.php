<?php

namespace App\Models;

/**
 * What a role may do. Not a table: the list is fixed by the website's features, so it lives here.
 * Admins tick these per role (role_permissions stores role_id + permission name).
 */
class Permission
{
    /** name => [label, group, what it allows] in the order the Roles & permissions screen shows them. */
    public const LIST = [
        'verify_payments' => ['Approve payments', 'Orders', 'Approve or reject payment slips'],
        'manage_orders' => ['Dispatch & deliver', 'Orders', 'Dispatch and deliver orders'],
        'cancel_orders' => ['Cancel orders', 'Orders', 'Cancel orders (their stock goes back)'],
        'manage_messages' => ['Customer messages', 'Orders', 'Read the messages inbox and reply to customers'],
        'manage_products' => ['Edit products & website', 'Catalog', 'Add and edit products and categories, the home showcase, the Sort By menu and the Website page'],
        'delete_products' => ['Delete from catalog', 'Catalog', 'Delete products, categories, Sort By options and suppliers'],
        'manage_promotions' => ['Promo codes', 'Catalog', 'Create, edit and turn off promo codes'],
        'manage_stock' => ['Stock & buying', 'Stock', 'Change stock counts, and create and receive purchase orders'],
        'manage_suppliers' => ['Suppliers', 'Stock', 'Add and edit suppliers'],
        'view_reports' => ['Reports', 'Business', 'Open the sales and stock reports'],
        'manage_payment_methods' => ['Payment methods', 'Business', 'Add, edit, turn off and delete payment methods'],
        'manage_users' => ['Accounts', 'Business', 'Create accounts, change roles, suspend accounts'],
    ];

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::LIST);
    }

    public static function label(string $name): string
    {
        return self::LIST[$name][0] ?? $name;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Something a role may do, like manage_stock. One row per permission in the permissions table;
 * admins tick them per role (role_permissions: role_id + permission_id, many-to-many).
 * LIST is the website's own list (the features that check them), used to fill the table and label the screen.
 */
class Permission extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

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

    /** @var array<string, int>|null name => id, kept for the request */
    private static ?array $ids = null;

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::LIST);
    }

    public static function label(string $name): string
    {
        return self::LIST[$name][0] ?? $name;
    }

    /** @return array<string, int> name => id */
    public static function ids(): array
    {
        return self::$ids ??= static::query()->pluck('id', 'name')->all();
    }

    public static function flushCache(): void
    {
        self::$ids = null;
    }
}

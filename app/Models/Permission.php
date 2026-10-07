<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Something a role may do, like manage_stock or view_reports. */
class Permission extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** How each permission is named and grouped on the Roles & permissions screen. */
    public const LABELS = [
        'verify_payments' => ['Approve payments', 'Orders'],
        'manage_orders' => ['Dispatch & deliver', 'Orders'],
        'cancel_orders' => ['Cancel orders', 'Orders'],
        'manage_messages' => ['Customer messages', 'Orders'],
        'manage_products' => ['Edit products & shop', 'Catalog'],
        'delete_products' => ['Delete from catalog', 'Catalog'],
        'manage_stock' => ['Stock & buying', 'Stock'],
        'manage_suppliers' => ['Suppliers', 'Stock'],
        'manage_promotions' => ['Promo codes', 'Catalog'],
        'view_reports' => ['Reports', 'Business'],
        'manage_payment_methods' => ['Payment methods', 'Business'],
        'manage_users' => ['Accounts', 'Business'],
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}

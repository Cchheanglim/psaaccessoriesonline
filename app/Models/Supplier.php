<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Where the shop buys its products. */
class Supplier extends Model
{
    protected $fillable = ['name', 'contact_name', 'phone', 'email', 'address', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}

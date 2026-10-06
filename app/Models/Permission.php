<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Something a role may do, like manage_stock or view_reports. */
class Permission extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}

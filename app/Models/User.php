<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Everyone who signs in (buyers, staff, admins). What they may do comes from their role;
 * shoppers also have a customer profile (1:1) with their loyalty points and addresses.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role_id',
        'role', // role name ('buyer', 'staff', 'admin' or one an admin made), saved as role_id
        'avatar_url',
        'banner_url',
        'status', // 'Active', 'Suspended'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->role_id ??= Role::idFor('buyer');
        });
    }

    /** The role's name in lower case ('buyer', 'staff', 'admin', ...); setting it stores the matching role_id. */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn () => Role::nameFor($this->role_id),
            set: fn (?string $value) => ['role_id' => Role::idFor(strtolower((string) $value) ?: 'buyer')],
        );
    }

    public function roleRecord()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }


    /** The shopper profile, created the first time it is needed (e.g. a staff member placing an order). */
    public function customerProfile(): Customer
    {
        if (! $this->customer) {
            $this->setRelation('customer', $this->customer()->create([]));
        }

        return $this->customer;
    }

    /** Admins can do everything; everyone else gets what their role's ticked permissions allow. */
    public function hasPermission(string $permission): bool
    {
        return $this->isAdmin() || in_array($permission, Role::permissionsFor($this->role_id), true);
    }

    /** @return list<string> */
    public function permissionNames(): array
    {
        if ($this->isAdmin()) {
            return Permission::where('is_active', true)->orderBy('id')->pluck('name')->all();
        }

        return $this->isStaff() ? Role::permissionsFor($this->role_id) : [];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Every role except buyer (staff, admin and roles an admin created) works in the staff portal. */
    public function isStaff(): bool
    {
        $role = $this->role;

        return $role !== null && $role !== 'buyer';
    }
}
